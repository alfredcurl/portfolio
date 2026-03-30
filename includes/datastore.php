<?php
// =============================================
// DataStore — MySQL-backed CMS content store
// Every section maps to a row in cms_sections
// with a JSON `content` column for flexible data
// =============================================

require_once ROOT_PATH . '/includes/config.php';

class DataStore
{

    // ── Ensure uploads directory exists ──────────────────
    public static function initUploads(): void
    {
        $dir = UPLOAD_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // ── Get a section's data from MySQL ──────────────────
    public static function get(string $section, bool $include_deleted = false): array
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
            return true;
        } catch (\Exception $e) {
            error_log("DataStore::save error: " . $e->getMessage());
            return false;
        }
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
                return $id;
            } else {
                DB::query("INSERT INTO $table SET " . implode(', ', $setPart) . ", created_at = NOW(), updated_at = NOW()", $params);
                return (int)DB::connect()->lastInsertId();
            }
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
                if ($count == 0) {
                    $defaults = self::getDefaults($section);
                    // Skills is special since it has tools/coding keys
                    if ($section === 'skills') {
                        foreach (($defaults['coding'] ?? []) as $i => $item) {
                            $item['type'] = 'coding';
                            $item['sort_order'] = $i;
                            self::saveItem('skills', $item);
                        }
                        foreach (($defaults['tools'] ?? []) as $i => $item) {
                            $item['type'] = 'tool';
                            $item['sort_order'] = $i;
                            self::saveItem('skills', $item);
                        }
                    } else {
                        foreach ($defaults as $i => $item) {
                            $item['sort_order'] = $i;
                            self::saveItem($section, $item);
                        }
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
                'photo'          => '/assets/img/IMG_20260304_010335_441.webp',
                'logo'           => '/assets/img/LogoWiz_02032026_173518.JPEG',
                'github'         => '#',
                'linkedin'       => '#',
                'twitter'        => '#',
                'instagram'      => '#',
                'hire_me_link'   => '#contact',
                'portfolio_link' => '#portfolio',
            ],
            'about' => [
                'bio1'  => 'I am a passionate <span class="text-green-500 font-semibold">Full-Stack Developer</span> and <span class="text-gold-500 font-semibold">Digital Creative</span> with over 8 years of experience delivering innovative digital solutions. Currently based in Kampala, Uganda, I sit at the unique intersection of technical engineering and artistic creation.',
                'bio2'  => 'Specializing in both front-end and back-end development, I combine technical proficiency with a creative mindset to craft user-centered, visually engaging, and high-performing web solutions. Whether I\'m teaching the next generation of devs at SaiPali Institute or building brands through Curl.Inc Media, my goal remains the same: <span class="text-white italic">to innovate and inspire.</span>',
                'stats' => [
                    ['value' => '8+',   'label' => 'Years Exp.'],
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
                    ['name' => 'React.js',    'icon' => 'fab fa-react',    'color' => 'text-blue-400',   'desc' => 'Frontend Library'],
                    ['name' => 'Node.js',     'icon' => 'fab fa-node',     'color' => 'text-green-500',  'desc' => 'Backend Runtime'],
                    ['name' => 'Adobe Suite', 'icon' => 'fab fa-adobe',    'color' => 'text-red-500',    'desc' => 'Ps, Ai, Id, Pr'],
                    ['name' => 'Photography', 'icon' => 'fas fa-camera',   'color' => 'text-slate-200',  'desc' => 'Visual Storytelling'],
                    ['name' => 'Git/GitHub',  'icon' => 'fab fa-git-alt',  'color' => 'text-orange-500', 'desc' => 'Version Control'],
                    ['name' => 'MySQL',       'icon' => 'fas fa-database', 'color' => 'text-blue-300',   'desc' => 'Database Mgmt'],
                ],
            ],
            'experience' => [
                [
                    'period'  => '2024 - Present',
                    'title'   => 'Junior IT Trainer',
                    'company' => 'SaiPali Institute of Technology and Management, Entebbe',
                    'bullets' => [
                        'Training diploma students in web development & software engineering.',
                        'Guiding hands-on projects in HTML, CSS, JS, and PHP.',
                        'Developing curriculum and digital learning resources.',
                    ],
                ],
                [
                    'period'  => '2013 - Present',
                    'title'   => 'Freelance Graphic Designer & Web Developer',
                    'company' => 'Self-Employed',
                    'bullets' => [
                        'Designing marketing collateral (logos, brochures, posters).',
                        'Building responsive, high-performance websites.',
                        'Providing web security, maintenance, and consultation.',
                    ],
                ],
            ],
            'portfolio' => [
                [
                    'title'       => 'Corporate Website',
                    'category'    => 'Web Development',
                    'cat_color'   => 'green',
                    'description' => 'Responsive corporate website with modern design and CMS integration.',
                    'icon'        => 'fas fa-globe',
                    'icon_color'  => 'text-green-500',
                    'bg_gradient' => 'from-green-500/20 to-gold-500/20',
                    'tags'        => ['WordPress', 'PHP', 'CSS'],
                    'link'        => '#',
                    'image'       => '',
                ],
                [
                    'title'       => 'Brand Identity Design',
                    'category'    => 'Branding',
                    'cat_color'   => 'gold',
                    'description' => 'Complete brand identity package including logo, colors, and marketing materials.',
                    'icon'        => 'fas fa-palette',
                    'icon_color'  => 'text-gold-500',
                    'bg_gradient' => 'from-gold-500/20 to-green-500/20',
                    'tags'        => ['Illustrator', 'Photoshop'],
                    'link'        => '#',
                    'image'       => '',
                ],
                [
                    'title'       => 'Online Store',
                    'category'    => 'E-Commerce',
                    'cat_color'   => 'blue',
                    'description' => 'Full-featured e-commerce platform with payment integration.',
                    'icon'        => 'fas fa-shopping-cart',
                    'icon_color'  => 'text-blue-400',
                    'bg_gradient' => 'from-blue-500/20 to-purple-500/20',
                    'tags'        => ['WooCommerce', 'JavaScript'],
                    'link'        => '#',
                    'image'       => '',
                ],
                [
                    'title'       => 'Mobile App Interface',
                    'category'    => 'UI/UX Design',
                    'cat_color'   => 'purple',
                    'description' => 'User-friendly mobile application interface with intuitive navigation.',
                    'icon'        => 'fas fa-mobile-alt',
                    'icon_color'  => 'text-purple-400',
                    'bg_gradient' => 'from-purple-500/20 to-pink-500/20',
                    'tags'        => ['Figma', 'React'],
                    'link'        => '#',
                    'image'       => '',
                ],
                [
                    'title'       => 'Photography Portfolio',
                    'category'    => 'Photography',
                    'cat_color'   => 'red',
                    'description' => 'Professional photography portfolio with gallery and client booking.',
                    'icon'        => 'fas fa-camera',
                    'icon_color'  => 'text-red-400',
                    'bg_gradient' => 'from-red-500/20 to-orange-500/20',
                    'tags'        => ['HTML5', 'Lightroom'],
                    'link'        => '#',
                    'image'       => '',
                ],
                [
                    'title'       => 'Business Dashboard',
                    'category'    => 'Web App',
                    'cat_color'   => 'cyan',
                    'description' => 'Analytics dashboard for business management with real-time data.',
                    'icon'        => 'fas fa-code-branch',
                    'icon_color'  => 'text-cyan-400',
                    'bg_gradient' => 'from-cyan-500/20 to-teal-500/20',
                    'tags'        => ['React', 'Node.js', 'MySQL'],
                    'link'        => '#',
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
