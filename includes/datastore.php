<?php
// =============================================
// DataStore — MySQL-backed CMS content store
// Every section maps to a row in cms_sections
// with a JSON `content` column for flexible data
// =============================================

require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/cache.php';

class DataStore
{
    private static $cache_ttl = 3600; // Cache for 1 hour

    // ── Ensure uploads directory exists ──────────────────
    public static function initUploads(): void
    {
        $dir = UPLOAD_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // ── Get a section's data from MySQL with caching ─────
    public static function get(string $section, bool $include_deleted = false): array
    {
        // Try cache first
        $cache_key = "section_{$section}_deleted_" . ($include_deleted ? '1' : '0');
        $cached = Cache::get($cache_key);
        if ($cached !== null) {
            return $cached;
        }
        
        // If not in cache, fetch from database
        $result = self::fetchFromDatabase($section, $include_deleted);
        
        // Store in cache
        Cache::set($cache_key, $result, self::$cache_ttl);
        
        return $result;
    }

    // ── Fetch data from MySQL ────────────────────────────
    private static function fetchFromDatabase(string $section, bool $include_deleted = false): array
    {
        $crud_map = [
            'portfolio'  => 'portfolio_projects',
            'experience' => 'experience_entries',
            'ventures'   => 'ventures_brands',
            'education'  => 'education_entries',
            'skills'     => 'skills_items'
        ];

        try {
            if (isset($crud_map[$section])) {
                $table = $crud_map[$section];
                $where = $include_deleted ? "1=1" : "deleted_at IS NULL";
                $rows  = DB::all("SELECT * FROM $table WHERE $where ORDER BY sort_order ASC, id ASC");

                // Post-process JSON columns
                $processed = array_map(function ($r) use ($section) {
                    if ($section === 'portfolio' && isset($r['tags_json'])) {
                        $r['tags'] = json_decode($r['tags_json'], true) ?: [];
                    }
                    if ($section === 'experience' && isset($r['bullets_json'])) {
                        $r['bullets'] = json_decode($r['bullets_json'], true) ?: [];
                    }
                    return $r;
                }, $rows);

                // Skills needs 'coding' and 'tools' as separate lists for public site
                if ($section === 'skills') {
                    $skills_structured = ['coding' => [], 'tools' => []];
                    foreach ($processed as $item) {
                        $type = ($item['type'] === 'tool') ? 'tools' : 'coding';
                        $skills_structured[$type][] = $item;
                    }
                    return $skills_structured;
                }

                return $processed;
            }

            // Default blob storage
            $row = DB::row(
                "SELECT content FROM cms_sections WHERE section_name = ? LIMIT 1",
                [$section]
            );
            if ($row && !empty($row['content'])) {
                $data = json_decode($row['content'], true);
                return is_array($data) ? $data : [];
            }
        } catch (\Exception $e) {
            error_log("DataStore::get error ($section): " . $e->getMessage());
        }

        return self::getDefaults($section);
    }

    // ── Save a section's data to MySQL ───────────────────
    public static function save(string $section, array $data): bool
    {
        // CRUD sections are saved record-by-record via saveItem
        // But we keep this for compatibility and blob sections (hero, about, contact)
        $blob_sections = ['hero', 'about', 'contact', 'site_settings'];
        if (!in_array($section, $blob_sections)) {
            // For CRUD sections, if we receive an array, we might want to do bulk save or sync
            // For now, let's just return true if it's a CRUD section to avoid errors
            return true;
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            $existing = DB::row("SELECT id FROM cms_sections WHERE section_name = ?", [$section]);
            if ($existing) {
                DB::query("UPDATE cms_sections SET content = ?, updated_at = NOW() WHERE section_name = ?", [$json, $section]);
            } else {
                DB::query("INSERT INTO cms_sections (section_name, content, created_at, updated_at) VALUES (?, ?, NOW(), NOW())", [$section, $json]);
            }
            
            // Clear cache after saving
            Cache::delete("section_{$section}_deleted_0");
            Cache::delete("section_{$section}_deleted_1");
            
            return true;
        } catch (\Exception $e) {
            error_log("DataStore::save error: " . $e->getMessage());
            return false;
        }
    }

    // ── Clear all cache ───────────────────────────────────
    public static function clearCache(?string $section = null): bool
    {
        if ($section) {
            Cache::delete("section_{$section}_deleted_0");
            Cache::delete("section_{$section}_deleted_1");
            return true;
        }
        return Cache::flush();
    }

    // ── CRUD Item Operations ─────────────────────────────

    public static function saveItem(string $section, array $rowData): ?int
    {
        $tableMap = [
            'portfolio'  => 'portfolio_projects',
            'experience' => 'experience_entries',
            'ventures'   => 'ventures_brands',
            'education'  => 'education_entries',
            'skills'     => 'skills_items'
        ];

        if (!isset($tableMap[$section])) return null;
        $table = $tableMap[$section];
        $id = (int)($rowData['id'] ?? 0);

        // Prep data
        $fields = [];
        $params = [];

        if ($section === 'portfolio') {
            $fields = ['title', 'category', 'cat_color', 'description', 'image', 'icon', 'icon_color', 'bg_gradient', 'tags_json', 'link', 'sort_order'];
            $rowData['tags_json'] = json_encode($rowData['tags'] ?? [], JSON_UNESCAPED_UNICODE);
        } elseif ($section === 'experience') {
            $fields = ['period', 'title', 'company', 'bullets_json', 'sort_order'];
            $rowData['bullets_json'] = json_encode($rowData['bullets'] ?? [], JSON_UNESCAPED_UNICODE);
        } elseif ($section === 'ventures') {
            $fields = ['initial', 'name', 'role', 'role_color', 'description', 'bg_color', 'logo', 'sort_order'];
        } elseif ($section === 'education') {
            $fields = ['degree', 'institution', 'period', 'sort_order'];
        } elseif ($section === 'skills') {
            $fields = ['type', 'name', 'percent', 'icon', 'color', 'description', 'sort_order'];
        }

        $setPart = [];
        foreach ($fields as $f) {
            $setPart[] = "`$f` = ?";
            $val = $rowData[$f] ?? null;
            
            // Handle defaults for NOT NULL or required fields
            if ($val === null) {
                if ($f === 'sort_order') $val = 0;
                elseif ($f === 'initial') $val = 'N';
                elseif ($f === 'type') $val = ($section === 'skills' ? 'coding' : '');
                else $val = ''; 
            }
            
            $params[] = $val;
        }

        try {
            if ($id > 0) {
                $params[] = $id;
                DB::query("UPDATE $table SET " . implode(', ', $setPart) . ", updated_at = NOW(), deleted_at = NULL WHERE id = ?", $params);
                $saved_id = $id;
            } else {
                DB::query("INSERT INTO $table SET " . implode(', ', $setPart) . ", created_at = NOW(), updated_at = NOW()", $params);
                $saved_id = (int)DB::connect()->lastInsertId();
            }
            self::clearCache($section);
            return $saved_id;
        } catch (\Exception $e) {
            error_log("DataStore::saveItem error: " . $e->getMessage());
            return null;
        }
    }

    public static function softDelete(string $section, int $id): bool
    {
        $tableMap = [
            'portfolio'  => 'portfolio_projects',
            'experience' => 'experience_entries',
            'ventures'   => 'ventures_brands',
            'education'  => 'education_entries',
            'skills'     => 'skills_items'
        ];
        if (!isset($tableMap[$section])) return false;
        $table = $tableMap[$section];
        try {
            DB::query("UPDATE $table SET deleted_at = NOW() WHERE id = ?", [$id]);
            self::clearCache($section);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function restore(string $section, int $id): bool
    {
        $tableMap = [
            'portfolio'  => 'portfolio_projects',
            'experience' => 'experience_entries',
            'ventures'   => 'ventures_brands',
            'education'  => 'education_entries',
            'skills'     => 'skills_items'
        ];
        if (!isset($tableMap[$section])) return false;
        $table = $tableMap[$section];
        try {
            DB::query("UPDATE $table SET deleted_at = NULL WHERE id = ?", [$id]);
            self::clearCache($section);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ── Initialize DB with defaults ──────────────────────
    public static function seedIfEmpty(): void
    {
        $blob_sections = ['hero', 'about', 'contact', 'site_settings'];
        foreach ($blob_sections as $section) {
            try {
                $row = DB::row("SELECT id FROM cms_sections WHERE section_name = ?", [$section]);
                if (!$row) {
                    self::save($section, self::getDefaults($section));
                }
            } catch (\Exception $e) {
            }
        }

        $crud_map = [
            'portfolio'  => 'portfolio_projects',
            'experience' => 'experience_entries',
            'ventures'   => 'ventures_brands',
            'education'  => 'education_entries',
            'skills'     => 'skills_items'
        ];

        foreach ($crud_map as $section => $table) {
            try {
                $count = DB::row("SELECT COUNT(*) as c FROM $table")['c'] ?? 0;
                if ($section === 'skills') {
                    $defaults = self::getDefaults($section);
                    // Backfill either skills group independently for older databases.
                    $coding_count = DB::row("SELECT COUNT(*) as c FROM $table WHERE type = 'coding'")['c'] ?? 0;
                    $tools_count = DB::row("SELECT COUNT(*) as c FROM $table WHERE type = 'tool'")['c'] ?? 0;
                    if ($coding_count == 0) {
                        foreach (($defaults['coding'] ?? []) as $i => $item) {
                            $item['type'] = 'coding';
                            $item['sort_order'] = $i;
                            self::saveItem('skills', $item);
                        }
                    }
                    if ($tools_count == 0) {
                        foreach (($defaults['tools'] ?? []) as $i => $item) {
                            $item['type'] = 'tool';
                            $item['sort_order'] = $i;
                            self::saveItem('skills', $item);
                        }
                    }
                } elseif ($count == 0) {
                    $defaults = self::getDefaults($section);
                    foreach ($defaults as $i => $item) {
                        $item['sort_order'] = $i;
                        self::saveItem($section, $item);
                    }
                }
            } catch (\Exception $e) {
            }
        }
    }

    // ── Default content ───────────────────────────────────
    public static function getDefaults(string $section): array
    {
        $defaults = [
            'hero' => [
                'greeting'       => 'Hello, I am',
                'name'           => 'Alfred Kaliisa',
                'title'          => 'Full-Stack Developer & Digital Creative',
                'photo'          => '/uploads/img_20260304_010335_441_69b06531c089f.webp',
                'logo'           => '/uploads/logowiz_02032026_173518_69b05669498e3.jpg',
                'github'         => '#',
                'linkedin'       => '#',
                'twitter'        => '#',
                'instagram'      => '#',
                'hire_me_link'   => '#contact',
                'portfolio_link' => '#portfolio',
            ],
            'about' => [
                'bio1'  => 'I am a passionate <span class="text-green-500 font-semibold">Full-Stack Developer</span> and <span class="text-gold-500 font-semibold">Digital Creative</span> with 3+ years of professional industry experience delivering innovative digital solutions. Currently based in Kampala, Uganda, I sit at the unique intersection of technical engineering and artistic creation.',
                'bio2'  => 'Specializing in both front-end and back-end development, I combine technical proficiency with a creative mindset to craft user-centered, visually engaging, and high-performing web solutions. Whether I\'m teaching the next generation of devs at SaiPali Institute or building brands through Curl.Inc Media, my goal remains the same: <span class="text-white italic">to innovate and inspire.</span>',
                'stats' => [
                    ['value' => '3+',   'label' => 'Years Exp.'],
                    ['value' => '50+',  'label' => 'Projects'],
                    ['value' => '2',    'label' => 'Ventures'],
                    ['value' => '100%', 'label' => 'Satisfaction'],
                ],
            ],
            'skills' => [
                'coding' => [
                    ['name' => 'HTML5',             'percent' => 100],
                    ['name' => 'JavaScript (ES6+)', 'percent' => 93],
                    ['name' => 'WordPress & CMS',   'percent' => 90],
                    ['name' => 'CSS3 / Tailwind',   'percent' => 90],
                    ['name' => 'PHP',               'percent' => 82],
                ],
                'tools' => [
                    ['name' => 'React.js',    'icon' => 'fab fa-react',    'color' => 'text-blue-400',   'description' => 'Frontend Library'],
                    ['name' => 'Node.js',     'icon' => 'fab fa-node',     'color' => 'text-green-500',  'description' => 'Backend Runtime'],
                    ['name' => 'Adobe Suite', 'icon' => 'fab fa-adobe',    'color' => 'text-red-500',    'description' => 'Ps, Ai, Id, Pr'],
                    ['name' => 'Photography', 'icon' => 'fas fa-camera',   'color' => 'text-slate-200',  'description' => 'Visual Storytelling'],
                    ['name' => 'Git/GitHub',  'icon' => 'fab fa-git-alt',  'color' => 'text-orange-500', 'description' => 'Version Control'],
                    ['name' => 'MySQL',       'icon' => 'fas fa-database', 'color' => 'text-blue-300',   'description' => 'Database Mgmt'],
                ],
            ],
            'experience' => [
                [
                    'period'  => '2024 - Present',
                    'title'   => 'IT Trainer & Curriculum Developer',
                    'company' => 'SaiPali Institute of Technology and Management, Entebbe',
                    'bullets' => [
                        'Training diploma students in web development & software engineering.',
                        'Guiding hands-on projects in HTML, CSS, JS, and PHP.',
                        'Developing curriculum and digital learning resources.',
                    ],
                ],
                [
                    'period'  => '2013 - Present',
                    'title'   => 'Freelance Web Developer & Digital Creative',
                    'company' => 'Independent Client Projects',
                    'bullets' => [
                        'Designing marketing collateral (logos, brochures, posters).',
                        'Building responsive, high-performance websites.',
                        'Providing web security, maintenance, and consultation.',
                    ],
                ],
            ],
            'portfolio' => [
                [
                    'title'       => 'Sai Pali Institute Website',
                    'category'    => 'Education Website',
                    'cat_color'   => 'green',
                    'description' => 'Role: Lead Web Developer & Content Strategist. Built and launched the institutional website for Sai Pali Institute with clear program pages, campus storytelling, and admissions-focused user flows to improve information clarity for prospective students.',
                    'icon'        => 'fas fa-graduation-cap',
                    'icon_color'  => 'text-green-500',
                    'bg_gradient' => 'from-green-500/20 to-gold-500/20',
                    'tags'        => ['Web Development', 'Responsive Design', 'Information Architecture', 'On-Page SEO'],
                    'link'        => 'https://saipali.education',
                    'image'       => '',
                ],
                [
                    'title'       => 'Onatti Foundation Website',
                    'category'    => 'Nonprofit Website',
                    'cat_color'   => 'blue',
                    'description' => 'Role: Web Developer & Digital Creative. Developed Onatti Foundation\'s web presence to communicate mission, programs, and donation journeys with stronger storytelling and clearer calls to action for supporters.',
                    'icon'        => 'fas fa-hand-holding-heart',
                    'icon_color'  => 'text-blue-400',
                    'bg_gradient' => 'from-blue-500/20 to-purple-500/20',
                    'tags'        => ['Nonprofit UX', 'Content Strategy', 'Donation Funnel UX', 'Accessibility'],
                    'link'        => 'https://www.onatti.org/',
                    'image'       => '',
                ],
                [
                    'title'       => 'Service Business Website & Booking Flow',
                    'category'    => 'Web Development',
                    'cat_color'   => 'gold',
                    'description' => 'Created a conversion-focused website with inquiry and booking workflows, improving lead quality and reducing response turnaround time.',
                    'icon'        => 'fas fa-globe',
                    'icon_color'  => 'text-gold-500',
                    'bg_gradient' => 'from-gold-500/20 to-green-500/20',
                    'tags'        => ['WordPress', 'Elementor', 'SEO'],
                    'link'        => '',
                    'image'       => '',
                ],
                [
                    'title'       => 'Brand Identity & Marketing Assets Suite',
                    'category'    => 'Branding',
                    'cat_color'   => 'purple',
                    'description' => 'Delivered complete visual identity systems including logos, color systems, and social campaign assets for small businesses.',
                    'icon'        => 'fas fa-palette',
                    'icon_color'  => 'text-purple-400',
                    'bg_gradient' => 'from-purple-500/20 to-pink-500/20',
                    'tags'        => ['Illustrator', 'Photoshop', 'Canva'],
                    'link'        => '',
                    'image'       => '',
                ],
            ],
            'ventures' => [
                [
                    'initial'     => 'C',
                    'name'        => 'Curl.Inc Media',
                    'role'        => 'Founder & Lead Creative',
                    'role_color'  => 'text-green-500',
                    'description' => 'A digital media brand focused on high-quality content creation and storytelling.',
                    'bg_color'    => 'bg-purple-500/10',
                    'logo'        => '',
                ],
                [
                    'initial'     => 'D',
                    'name'        => 'Digital Insanity',
                    'role'        => 'Principal Innovator',
                    'role_color'  => 'text-gold-500',
                    'description' => 'A forward-thinking tech initiative exploring unconventional digital solutions.',
                    'bg_color'    => 'bg-green-500/10',
                    'logo'        => '',
                ],
            ],
            'education' => [
                [
                    'degree'      => 'Diploma in Software Engineering',
                    'institution' => 'SaiPali Institute of Technology & Management',
                    'period'      => '2023-2025',
                ],
                [
                    'degree'      => 'A-Level Certificate',
                    'institution' => 'Maanji Memorial Academy',
                    'period'      => '2007-2008',
                ],
            ],
            'contact' => [
                'phone'          => '+256 765 751 687',
                'phone_link'     => 'tel:+256765751687',
                'email'          => 'alfred.whisles.kalisa853@gmail.com',
                'website'        => 'https://alfred.chrysalisdigitals.com',
                'location'       => 'Kampala, Uganda',
                'availability'   => 'Freelance Available',
                'copyright_year' => '2024',
            ],
            'site_settings' => [
                'site_title'       => 'Alfred Kaliisa | Full-Stack Developer & Digital Creative',
                'meta_description' => 'Alfred Kaliisa - Full-Stack Developer and Digital Creative based in Kampala, Uganda.',
                'nav_links'        => ['About', 'Skills', 'Experience', 'Portfolio', 'Ventures', 'Contact'],
            ],
        ];

        return $defaults[$section] ?? [];
    }
}

DataStore::initUploads();
