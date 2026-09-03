<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/datastore.php';

// Load all sections
$hero        = DataStore::get('hero');
$about       = DataStore::get('about');
$skills      = DataStore::get('skills');
$experience  = DataStore::get('experience');
$portfolio   = DataStore::get('portfolio');
$ventures    = DataStore::get('ventures');
$education   = DataStore::get('education');
$contact     = DataStore::get('contact');
$settings    = DataStore::get('site_settings');

$script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$app_base_url = dirname($script_path);
if (basename($app_base_url) === 'public') {
    $app_base_url = dirname($app_base_url);
}
$app_base_url = $app_base_url === '/' || $app_base_url === '.' ? '' : rtrim($app_base_url, '/');
$asset_url = static function (?string $url) use ($app_base_url): string {
    if (!$url || preg_match('~^(https?:|data:|#)~', $url) || $url[0] !== '/') {
        return $url ?? '';
    }
    return $app_base_url . $url;
};

$cat_colors = [
    'green'  => ['bg' => 'bg-green-500/20', 'text' => 'text-green-500'],
    'gold'   => ['bg' => 'bg-gold-500/20',  'text' => 'text-gold-500'],
    'blue'   => ['bg' => 'bg-blue-500/20',  'text' => 'text-blue-400'],
    'purple' => ['bg' => 'bg-purple-500/20', 'text' => 'text-purple-400'],
    'red'    => ['bg' => 'bg-red-500/20',   'text' => 'text-red-400'],
    'cyan'   => ['bg' => 'bg-cyan-500/20',  'text' => 'text-cyan-400'],
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    $site_title    = htmlspecialchars($settings['site_title']       ?? 'Alfred Kaliisa | Full-Stack Developer & Digital Creative');
    $meta_desc     = htmlspecialchars($settings['meta_description'] ?? 'Alfred Kaliisa is a Full-Stack Developer and Digital Creative based in Kampala, Uganda. 3+ years delivering web development, branding, and digital solutions.');
    $hero_name     = htmlspecialchars($hero['name']   ?? 'Alfred Kaliisa');
    $hero_title    = htmlspecialchars($hero['title']  ?? 'Full-Stack Developer & Digital Creative');
    $hero_photo    = htmlspecialchars($asset_url($hero['photo'] ?? '/uploads/img_20260304_010335_441_69b06531c089f.webp'));
    $canonical_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'alfred.chrysalisdigitals.com') . '/';
    ?>

    <title><?= $site_title ?></title>
    <meta name="description" content="<?= $meta_desc ?>">
    <meta name="keywords" content="Alfred Kaliisa, Full-Stack Developer, Web Developer Uganda, Graphic Designer Kampala, PHP Developer, WordPress Developer, Freelance Developer Uganda, Digital Creative">
    <meta name="author" content="<?= $hero_name ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $canonical_url ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= $canonical_url ?>sitemap.xml">

    <!-- Open Graph / Facebook -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= $canonical_url ?>">
    <meta property="og:title"       content="<?= $site_title ?>">
    <meta property="og:description" content="<?= $meta_desc ?>">
    <meta property="og:image"       content="<?= $hero_photo ?>">
    <meta property="og:locale"      content="en_UG">
    <meta property="og:site_name"   content="<?= $hero_name ?> Portfolio">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $site_title ?>">
    <meta name="twitter:description" content="<?= $meta_desc ?>">
    <meta name="twitter:image"       content="<?= $hero_photo ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="<?= htmlspecialchars($asset_url($hero['logo'] ?? '/uploads/logowiz_02032026_173518_69b05669498e3.jpg')) ?>">

    <!-- JSON-LD Structured Data (Person) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Person",
      "name": "<?= addslashes($hero['name'] ?? 'Alfred Kaliisa') ?>",
      "url": "<?= $canonical_url ?>",
    "image": "<?= addslashes($asset_url($hero['photo'] ?? '')) ?>",
      "jobTitle": "<?= addslashes($hero['title'] ?? 'Full-Stack Developer & Digital Creative') ?>",
      "description": "<?= addslashes($settings['meta_description'] ?? '') ?>",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "<?= addslashes($contact['location'] ?? 'Kampala') ?>",
        "addressCountry": "UG"
      },
      "email": "<?= addslashes($contact['email'] ?? '') ?>",
      "telephone": "<?= addslashes($contact['phone'] ?? '') ?>",
      "sameAs": [
        "<?= addslashes($hero['github']    ?? '') ?>",
        "<?= addslashes($hero['linkedin']  ?? '') ?>",
        "<?= addslashes($hero['twitter']   ?? '') ?>",
        "<?= addslashes($hero['instagram'] ?? '') ?>"
      ]
    }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/tailwind.min.css">

    <!-- React & jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script crossorigin src="https://cdn.jsdelivr.net/npm/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://cdn.jsdelivr.net/npm/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@babel/standalone@7/babel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/Observer.min.js"></script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        .delay-100 {
            animation-delay: .1s;
        }

        .delay-200 {
            animation-delay: .2s;
        }

        .delay-300 {
            animation-delay: .3s;
        }

        .text-gradient {
            background: linear-gradient(135deg, #66BB6A, #FFA000);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .glow-on-hover {
            transition: all .3s ease;
        }

        .glow-on-hover:hover {
            box-shadow: 0 0 20px rgba(102, 187, 106, .5), 0 0 40px rgba(255, 160, 0, .3);
            transform: translateY(-5px);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-20px)
            }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px)
            }

            to {
                opacity: 1;
                transform: translateX(0)
            }
        }

        .slide-in-left {
            animation: slideInLeft .8s ease-out forwards;
        }

        .scale-hover {
            transition: transform .3s ease;
        }

        .scale-hover:hover {
            transform: scale(1.05);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #66BB6A, #FFA000);
            border-radius: 4px;
        }

        /* Skill bar animation */
        .skill-bar {
            width: 0;
            transition: width 1.5s ease-in-out;
        }

        /* Mobile menu */
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }

        #mobile-menu.open {
            max-height: 400px;
        }

        /* Dark Mode Transitions */
        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .dark .bg-navy-900 {
            background-color: #0f172a;
        }

        .dark .bg-navy-800 {
            background-color: #1e293b;
        }

        /* Light Mode Overrides */
        html:not(.dark) body {
            background-color: #ffffff;
            color: #475569;
        }

        html:not(.dark) .bg-navy-900 {
            background-color: #ffffff;
        }

        html:not(.dark) .bg-navy-800 {
            background-color: #f8fafc;
        }

        html:not(.dark) .bg-navy-950 {
            background-color: #f1f5f9;
        }

        /* Navigation & Scrollbar */
        html:not(.dark) .bg-navy-900\/90 {
            background-color: rgba(255, 255, 255, 0.9);
        }

        html:not(.dark) .bg-navy-900\/95 {
            background-color: rgba(255, 255, 255, 0.95);
        }

        html:not(.dark) ::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        html:not(.dark) #navbar {
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.05);
        }

        /* Typography */
        html:not(.dark) .text-slate-300 {
            color: #475569;
        }

        html:not(.dark) .text-slate-400 {
            color: #57687a;
        }

        html:not(.dark) .text-white {
            color: #0f172a;
        }

        html:not(.dark) h2,
        html:not(.dark) h3,
        html:not(.dark) h4 {
            color: #020617;
        }

        /* Borders */
        html:not(.dark) .border-white\/10 {
            border-color: #e2e8f0;
        }

        html:not(.dark) .border-white\/5 {
            border-color: #f1f5f9;
        }

        /* UI Components */
        html:not(.dark) .group\/card,
        html:not(.dark) .cms-card,
        html:not(.dark) .group.relative.overflow-hidden.rounded-lg.bg-navy-800 {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.03);
        }

        /* Tool Cards & Skill Tracks */
        html:not(.dark) .bg-navy-800.border-white\/5.rounded {
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }

        html:not(.dark) .h-2.bg-navy-800.rounded-full {
            background-color: #f1f5f9;
        }

        /* Ventures Section (Brands) Overrides */
        html:not(.dark) #ventures .bg-navy-800 {
            background-color: #ffffff;
            border-color: #e2e8f0;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.05);
        }

        html:not(.dark) #ventures .from-navy-950 {
            --tw-gradient-from: #f1f5f9;
            --tw-gradient-to: rgb(241 245 249 / 0);
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
        }

        html:not(.dark) #ventures .w-16.h-16.bg-white {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        /* Footer / Contact Section Overrides */
        html:not(.dark) #contact.bg-navy-950 {
            background-color: #f8fafc;
        }

        html:not(.dark) #contact .bg-navy-900 {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
        }

        html:not(.dark) #contact .border-t.border-white\/5 {
            border-color: #e2e8f0;
        }

        html:not(.dark) #contact .text-slate-500 {
            color: #64748b;
        }

        /* Forms */
        html:not(.dark) input,
        html:not(.dark) textarea {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #1e293b !important;
        }

        html:not(.dark) input::placeholder,
        html:not(.dark) textarea::placeholder {
            color: #94a3b8 !important;
        }

        /* Branding Colors */
        html:not(.dark) .text-green-500 {
            color: #059669;
        }

        html:not(.dark) .text-gold-500 {
            color: #d6831d;
        }
    </style>
    <script>
        // Init Theme
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>

<body class="bg-navy-900 text-slate-300 font-sans antialiased selection:bg-gold-500 selection:text-navy-900 overflow-x-hidden">

    <!-- ========= NAVIGATION ========= -->
    <nav id="navbar" class="fixed w-full z-50 bg-navy-900/90 backdrop-blur-md border-b border-white/10 transition-all">
        <div class="max-w-6xl mx-auto px-4 md:px-6 py-3 flex justify-between items-center">
            <a href="#home" class="flex items-center">
                <img src="<?= htmlspecialchars($asset_url($hero['logo'] ?? '/uploads/logowiz_02032026_173518_69b05669498e3.jpg')) ?>"
                    style="height:52px;" alt="<?= htmlspecialchars($hero['name']) ?> Logo"
                    class="w-auto hover:scale-110 transition-transform duration-300">
            </a>
            <div class="hidden md:flex space-x-8 text-sm font-semibold uppercase tracking-widest text-slate-400">
                <?php foreach (($settings['nav_links'] ?? ['About', 'Skills', 'Experience', 'Portfolio', 'Ventures', 'Contact']) as $link): ?>
                    <a href="#<?= strtolower($link) ?>" class="hover:text-green-500 transition-colors"><?= htmlspecialchars($link) ?></a>
                <?php endforeach; ?>
                <a href="<?= htmlspecialchars($app_base_url) ?>/admin/index.php" class="border-l border-white/10 pl-8 text-green-500 hover:text-white transition-colors">Login</a>
            </div>
            <!-- Hamburger -->
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="text-green-500 text-xl hover:scale-110 transition-transform" aria-label="Toggle Theme">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>
                <button id="hamburger" class="md:hidden text-green-500 text-2xl focus:outline-none" aria-label="Toggle menu">
                    <i class="fas fa-bars" id="hamburger-icon"></i>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden bg-navy-900/95 px-6 pb-4">
            <?php foreach (($settings['nav_links'] ?? ['About', 'Skills', 'Experience', 'Portfolio', 'Ventures', 'Contact']) as $link): ?>
                <a href="#<?= strtolower($link) ?>" class="block py-3 text-sm font-semibold uppercase tracking-widest text-slate-400 hover:text-green-500 transition-colors border-b border-white/5 mobile-menu-link">
                    <?= htmlspecialchars($link) ?>
                </a>
            <?php endforeach; ?>
            <a href="<?= htmlspecialchars($app_base_url) ?>/admin/index.php" class="block py-3 text-sm font-semibold uppercase tracking-widest text-green-500 hover:text-white transition-colors border-b border-white/5 mobile-menu-link">CMS Login</a>
        </div>
    </nav>

    <!-- React App Root (dynamic components rendered here) -->
    <div id="react-root"></div>

    <!-- ========= HERO SECTION ========= -->
    <section id="home" class="min-h-screen flex items-center justify-center pt-20 relative overflow-hidden">
        <div class="absolute top-20 right-0 w-64 md:w-96 h-64 md:h-96 bg-green-500/10 rounded-full blur-3xl -z-10 float-animation"></div>
        <div class="absolute bottom-0 left-0 w-48 md:w-72 h-48 md:h-72 bg-gold-500/10 rounded-full blur-3xl -z-10 float-animation" style="animation-delay:3s"></div>

        <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-8 md:gap-12 items-center w-full">
            <div class="order-2 md:order-1 animate-fade-in">
                <h2 class="text-green-500 font-bold tracking-widest uppercase mb-2 text-sm slide-in-left">
                    <?= htmlspecialchars($hero['greeting'] ?? 'Hello, I am') ?>
                </h2>
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-serif font-bold text-white mb-2 leading-tight">
                    <?php
                    $name_parts = explode(' ', $hero['name'] ?? 'Alfred Kaliisa', 2);
                    echo htmlspecialchars($name_parts[0]);
                    if (isset($name_parts[1])): ?>
                        <span class="text-gradient"> <?= htmlspecialchars($name_parts[1]) ?></span>
                    <?php endif; ?>
                </h1>
                <p class="text-lg sm:text-xl md:text-2xl text-slate-400 mb-6 font-light animate-fade-in delay-200">
                    <?= htmlspecialchars($hero['title'] ?? 'Full-Stack Developer & Digital Creative') ?>
                </p>
                <div class="flex flex-wrap gap-3 md:gap-4">
                    <a href="<?= htmlspecialchars($hero['hire_me_link'] ?? '#contact') ?>"
                        class="px-6 md:px-8 py-3 bg-gradient-to-r from-green-500 to-gold-500 text-white font-bold rounded hover:shadow-lg hover:shadow-green-500/50 transition-all transform hover:-translate-y-1">
                        Hire Me
                    </a>
                    <a href="<?= htmlspecialchars($hero['portfolio_link'] ?? '#portfolio') ?>"
                        class="px-6 md:px-8 py-3 border-2 border-green-500 text-slate-300 font-bold rounded hover:bg-green-500 hover:text-white transition-all">
                        View Portfolio
                    </a>
                </div>
                <div class="mt-8 md:mt-10 flex gap-6 text-slate-500">
                    <a href="<?= htmlspecialchars($hero['github'] ?? '#') ?>" aria-label="GitHub" class="hover:text-green-500 text-2xl transition-all hover:scale-125"><i class="fab fa-github"></i></a>
                    <a href="<?= htmlspecialchars($hero['linkedin'] ?? '#') ?>" aria-label="LinkedIn" class="hover:text-green-500 text-2xl transition-all hover:scale-125"><i class="fab fa-linkedin"></i></a>
                    <a href="<?= htmlspecialchars($hero['twitter'] ?? '#') ?>" aria-label="X (formerly Twitter)" class="hover:text-green-500 text-2xl transition-all hover:scale-125"><span class="social-x-icon font-bold text-xl leading-none" aria-hidden="true">X</span></a>
                    <a href="<?= htmlspecialchars($hero['instagram'] ?? '#') ?>" aria-label="Instagram" class="hover:text-green-500 text-2xl transition-all hover:scale-125"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="order-1 md:order-2 flex justify-center animate-fade-in delay-200">
                <div id="hero-portrait" class="relative mx-4">
                    <div class="absolute inset-0 border-4 border-green-500 rounded-lg transform translate-x-4 translate-y-4 md:translate-x-6 md:translate-y-6"></div>
                    <img src="<?= htmlspecialchars($asset_url($hero['photo'] ?? '/uploads/img_20260304_010335_441_69b06531c089f.webp')) ?>"
                        alt="<?= htmlspecialchars($hero['name'] ?? 'Alfred Kaliisa') ?> - Portfolio Photo"
                        class="w-52 h-52 sm:w-64 sm:h-64 md:w-96 md:h-96 object-cover rounded-lg border-4 border-gold-500 shadow-2xl relative z-10 hover:shadow-green-500/50 transition-all duration-700 glow-on-hover">
                </div>
            </div>
        </div>
    </section>

    <div class="relative overflow-hidden border-y border-white/10 bg-navy-950 py-3 text-xs font-bold uppercase tracking-[0.35em] text-green-500" aria-hidden="true">
        <div class="portfolio-marquee flex w-max gap-10 whitespace-nowrap">
            <span>Code with character</span><span class="text-gold-500">&bull;</span><span>Ideas in motion</span><span class="text-gold-500">&bull;</span><span>Digital craft, Kampala</span><span class="text-gold-500">&bull;</span>
            <span>Code with character</span><span class="text-gold-500">&bull;</span><span>Ideas in motion</span><span class="text-gold-500">&bull;</span><span>Digital craft, Kampala</span><span class="text-gold-500">&bull;</span>
        </div>
    </div>

    <!-- ========= ABOUT SECTION ========= -->
    <section id="about" class="py-24 bg-navy-800 relative">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-4">About Me</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-gold-500 mx-auto"></div>
            </div>
            <div class="bg-navy-900 p-8 md:p-12 rounded-lg border border-white/5 shadow-xl relative">
                <i class="fas fa-quote-left text-gold-500/20 text-6xl absolute top-8 left-8"></i>
                <p class="text-lg leading-relaxed text-slate-300 relative z-10 text-justify">
                    <?= $about['bio1'] ?? '' ?>
                </p>
                <p class="text-lg leading-relaxed text-slate-300 mt-6 relative z-10 text-justify">
                    <?= $about['bio2'] ?? '' ?>
                </p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-10 pt-10 border-t border-white/10 text-center">
                    <?php foreach (($about['stats'] ?? []) as $stat): ?>
                        <div>
                            <span class="stat-value block text-3xl font-bold text-white mb-1"><?= htmlspecialchars($stat['value']) ?></span>
                            <span class="text-xs uppercase tracking-wider text-slate-500"><?= htmlspecialchars($stat['label']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ========= SKILLS SECTION ========= -->
    <section id="skills" class="py-24 bg-navy-900">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-4">Technical Arsenal</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-gold-500 mx-auto"></div>
            </div>
            <div class="grid md:grid-cols-2 gap-12">
                <!-- Coding Skills -->
                <div>
                    <h3 class="text-xl font-bold text-white mb-8 flex items-center">
                        <i class="fas fa-code text-green-500 mr-3"></i> Development
                    </h3>
                    <div class="space-y-6" id="skill-bars">
                        <?php foreach (($skills['coding'] ?? []) as $skill): ?>
                            <div>
                                <div class="flex justify-between mb-2">
                                    <span class="font-semibold text-slate-300"><?= htmlspecialchars($skill['name']) ?></span>
                                    <span class="text-green-500"><?= intval($skill['percent']) ?>%</span>
                                </div>
                                <div class="h-2 bg-navy-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-500 to-gold-500 skill-bar"
                                        data-width="<?= intval($skill['percent']) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tools Grid -->
                <div>
                    <h3 class="text-xl font-bold text-white mb-8 flex items-center">
                        <i class="fas fa-layer-group text-gold-500 mr-3"></i> Design & Tools
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach (($skills['tools'] ?? []) as $tool): ?>
                            <div class="p-4 bg-navy-800 border border-white/5 rounded hover:border-green-500 scale-hover transition-all glow-on-hover">
                                <i class="<?= htmlspecialchars($tool['icon']) ?> <?= htmlspecialchars($tool['color']) ?> text-3xl mb-3"></i>
                                <h4 class="font-bold text-white"><?= htmlspecialchars($tool['name']) ?></h4>
                                <p class="text-xs text-slate-400"><?= htmlspecialchars($tool['description']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ========= EXPERIENCE SECTION ========= -->
    <section id="experience" class="py-24 bg-navy-800">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-4">Professional Journey</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-gold-500 mx-auto"></div>
            </div>
            <div class="space-y-10 pl-6 border-l-2 border-green-500/30">
                <?php foreach (($experience ?? []) as $exp): ?>
                    <div class="relative">
                        <!-- Timeline dot -->
                        <div class="absolute -left-[31px] top-3 w-4 h-4 rounded-full bg-green-500 border-4 border-gold-500 animate-pulse"></div>
                        <div class="bg-navy-900 p-5 md:p-6 rounded-lg border border-white/5 shadow-lg hover:shadow-green-500/30 transition-all glow-on-hover">
                            <span class="text-green-500 text-xs font-bold uppercase mb-1 block"><?= htmlspecialchars($exp['period']) ?></span>
                            <h3 class="text-lg md:text-xl font-bold text-white"><?= htmlspecialchars($exp['title']) ?></h3>
                            <p class="text-slate-400 text-sm mb-4"><?= htmlspecialchars($exp['company']) ?></p>
                            <ul class="text-slate-300 text-sm space-y-2 list-disc list-inside marker:text-green-500">
                                <?php foreach (($exp['bullets'] ?? []) as $bullet): ?>
                                    <li><?= htmlspecialchars($bullet) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========= PORTFOLIO SECTION ========= -->
    <section id="portfolio" class="py-24 bg-navy-900">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-4">Portfolio Showcase</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-gold-500 mx-auto mb-6"></div>
                <p class="text-slate-400 max-w-2xl mx-auto">A curated selection of my best work in web development and digital design.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (($portfolio ?? []) as $project): ?>
                    <?php
                    $cc = $cat_colors[$project['cat_color'] ?? 'green'] ?? $cat_colors['green'];
                    ?>
                    <div class="group relative overflow-hidden rounded-lg bg-navy-800 border border-white/5 hover:border-green-500 transition-all glow-on-hover">
                        <div class="aspect-video bg-navy-800 flex items-center justify-center relative overflow-hidden">
                            <?php if (!empty($project['image'])): ?>
                                <img src="<?= htmlspecialchars($asset_url($project['image'])) ?>" alt="<?= htmlspecialchars($project['title']) ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br <?= htmlspecialchars($project['bg_gradient']) ?> flex items-center justify-center">
                                    <i class="<?= htmlspecialchars($project['icon']) ?> text-6xl <?= htmlspecialchars($project['icon_color']) ?> opacity-50 group-hover:scale-110 transition-transform"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 <?= $cc['bg'] ?> <?= $cc['text'] ?> text-xs rounded font-bold"><?= htmlspecialchars($project['category']) ?></span>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2"><?= htmlspecialchars($project['title']) ?></h3>
                            <p class="text-slate-400 text-sm mb-4"><?= htmlspecialchars($project['description']) ?></p>
                            <div class="flex gap-2 flex-wrap text-xs text-slate-500">
                                <?php foreach (($project['tags'] ?? []) as $tag): ?>
                                    <span class="bg-navy-900 px-2 py-1 rounded"><?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($project['link']) && $project['link'] !== '#'): ?>
                                <a href="<?= htmlspecialchars($project['link']) ?>" target="_blank"
                                    class="mt-4 inline-block text-green-500 text-xs font-bold hover:text-gold-500 transition-colors">
                                    View Project <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========= VENTURES SECTION ========= -->
    <section id="ventures" class="py-24 bg-navy-900">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-4">My Brands</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-gold-500 mx-auto mb-6"></div>
                <p class="text-slate-400 max-w-2xl mx-auto">Where technical skill meets creative vision.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <?php foreach (($ventures ?? []) as $venture): ?>
                    <div class="group relative overflow-hidden rounded-xl border border-white/10 bg-navy-800">
                        <div class="absolute inset-0 bg-gradient-to-t from-navy-950 to-transparent opacity-90 z-10"></div>
                        <div class="absolute top-0 right-0 w-64 h-64 <?= htmlspecialchars($venture['bg_color']) ?> rounded-full blur-3xl -z-0"></div>
                        <div class="relative z-20 p-10 h-full flex flex-col justify-end min-h-[300px] group-hover:-translate-y-2 transition-transform duration-300">
                            <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center mb-6 text-navy-900 text-2xl font-bold overflow-hidden">
                                <?php if (!empty($venture['logo'])): ?>
                                    <img src="<?= htmlspecialchars($asset_url($venture['logo'])) ?>" alt="<?= htmlspecialchars($venture['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?= htmlspecialchars($venture['initial']) ?>
                                <?php endif; ?>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($venture['name']) ?></h3>
                            <p class="<?= htmlspecialchars($venture['role_color']) ?> text-sm font-bold uppercase tracking-wider mb-4"><?= htmlspecialchars($venture['role']) ?></p>
                            <p class="text-slate-300 text-sm leading-relaxed"><?= htmlspecialchars($venture['description']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========= EDUCATION SECTION ========= -->
    <section id="education" class="py-20 bg-navy-800">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-10">
                <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-4">Education</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-gold-500 mx-auto"></div>
            </div>
            <div class="grid sm:grid-cols-2 gap-6">
                <?php foreach (($education ?? []) as $edu): ?>
                    <div class="bg-navy-900 p-6 rounded border border-white/5 hover:border-green-500/50 transition-all glow-on-hover">
                        <div class="flex flex-col gap-2 mb-2">
                            <span class="text-green-500 text-xs font-bold bg-green-500/10 px-2 py-1 rounded self-start"><?= htmlspecialchars($edu['period']) ?></span>
                            <h4 class="font-bold text-white text-base md:text-lg leading-snug"><?= htmlspecialchars($edu['degree']) ?></h4>
                        </div>
                        <p class="text-slate-400 text-sm"><?= htmlspecialchars($edu['institution']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ========= CONTACT SECTION ========= -->
    <section id="contact" class="py-24 bg-navy-950 border-t border-white/5">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-serif font-bold text-white mb-6">Let's Create Together</h2>
            <p class="text-slate-400 mb-12 max-w-lg mx-auto">
                Ready to start your next project with a professional who understands both code and design?
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-8">
                <a href="<?= htmlspecialchars($contact['phone_link'] ?? '#') ?>"
                    class="group p-8 bg-navy-900 rounded-lg hover:bg-gradient-to-br hover:from-green-500 hover:to-gold-500 transition-all glow-on-hover">
                    <i class="fas fa-phone text-3xl text-green-500 group-hover:text-white mb-4 transition-colors block"></i>
                    <h3 class="text-white font-bold mb-2">Phone</h3>
                    <p class="text-slate-400 group-hover:text-white text-sm"><?= htmlspecialchars($contact['phone'] ?? '') ?></p>
                </a>
                <a href="mailto:<?= htmlspecialchars($contact['email'] ?? '') ?>"
                    class="group p-8 bg-navy-900 rounded-lg hover:bg-gradient-to-br hover:from-green-500 hover:to-gold-500 transition-all glow-on-hover">
                    <i class="fas fa-envelope text-3xl text-green-500 group-hover:text-white mb-4 transition-colors block"></i>
                    <h3 class="text-white font-bold mb-2">Email</h3>
                    <p class="text-slate-400 group-hover:text-white text-sm">Click to Email Me</p>
                </a>
                <a href="<?= htmlspecialchars($contact['website'] ?? '#') ?>" target="_blank"
                    class="group p-8 bg-navy-900 rounded-lg hover:bg-gradient-to-br hover:from-green-500 hover:to-gold-500 transition-all glow-on-hover">
                    <i class="fas fa-globe text-3xl text-gold-500 group-hover:text-white mb-4 transition-colors block"></i>
                    <h3 class="text-white font-bold mb-2">Website</h3>
                    <p class="text-slate-400 group-hover:text-white text-sm">Visit Portfolio</p>
                </a>
            </div>

            <!-- Contact Form (React Component) -->
            <div id="contact-form-root" class="mt-16"></div>

            <div class="mt-16 pt-8 border-t border-white/5 text-slate-500 text-sm">
                <p>© <?= htmlspecialchars($contact['copyright_year'] ?? date('Y')) ?> <?= htmlspecialchars($hero['name'] ?? 'Alfred Kaliisa') ?>. All Rights Reserved.</p>
                <div class="mt-2 text-xs"><?= htmlspecialchars($contact['location'] ?? 'Kampala, Uganda') ?> • <?= htmlspecialchars($contact['availability'] ?? 'Freelance Available') ?></div>
            </div>
        </div>
    </section>

    <!-- Back to Top -->
    <div id="scroll-progress" class="fixed left-0 top-0 z-[60] h-1 w-0 origin-left bg-gradient-to-r from-green-500 via-gold-500 to-green-500" aria-hidden="true"></div>
    <a href="#home" class="fixed bottom-8 right-8 bg-gradient-to-r from-green-500 to-gold-500 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg hover:-translate-y-1 hover:shadow-green-500/50 transition-all z-50">
        <i class="fas fa-arrow-up"></i>
    </a>

    <!-- React Components (Contact Form + Skill Bars) -->
    <script type="text/babel">
        const { useState, useEffect } = React;

    // ===== ANIMATED SKILL BARS =====
    function SkillBars() {
      useEffect(() => {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const bars = document.querySelectorAll('.skill-bar');
              bars.forEach(bar => {
                bar.style.width = bar.dataset.width;
              });
              observer.disconnect();
            }
          });
        }, { threshold: 0.3 });
        const section = document.getElementById('skill-bars');
        if (section) observer.observe(section);
        return () => observer.disconnect();
      }, []);
      return null;
    }

    // ===== CONTACT FORM =====
    function ContactForm() {
      const [form, setForm] = useState({ name: '', email: '', subject: '', message: '' });
      const [status, setStatus] = useState(null); // null | 'sending' | 'success' | 'error'

      const handleSubmit = (e) => {
        e.preventDefault();
        setStatus('sending');
        $.ajax({
          url: '<?= htmlspecialchars($app_base_url) ?>/api/contact.php',
          type: 'POST',
          contentType: 'application/json',
          data: JSON.stringify(form),
          dataType: 'json',
          success: (data) => {
            if (data.success) {
              setStatus('success');
              setForm({ name: '', email: '', subject: '', message: '' });
            } else {
              setStatus('error');
            }
          },
          error: () => setStatus('error')
        });
      };

      return (
        <div className="mt-4 max-w-2xl mx-auto text-left">
          <h3 className="text-2xl font-bold text-white mb-6 text-center font-serif">Send a Message</h3>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="block text-slate-400 text-sm mb-1">Your Name</label>
                <input type="text" required value={form.name}
                  onChange={e => setForm({...form, name: e.target.value})}
                  className="w-full bg-navy-800 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-green-500 transition-colors"
                  placeholder="John Doe" />
              </div>
              <div>
                <label className="block text-slate-400 text-sm mb-1">Email Address</label>
                <input type="email" required value={form.email}
                  onChange={e => setForm({...form, email: e.target.value})}
                  className="w-full bg-navy-800 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-green-500 transition-colors"
                  placeholder="john@example.com" />
              </div>
            </div>
            <div>
              <label className="block text-slate-400 text-sm mb-1">Subject</label>
              <input type="text" required value={form.subject}
                onChange={e => setForm({...form, subject: e.target.value})}
                className="w-full bg-navy-800 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-green-500 transition-colors"
                placeholder="Project Inquiry" />
            </div>
            <div>
              <label className="block text-slate-400 text-sm mb-1">Message</label>
              <textarea required rows="5" value={form.message}
                onChange={e => setForm({...form, message: e.target.value})}
                className="w-full bg-navy-800 border border-white/10 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-green-500 transition-colors resize-none"
                placeholder="Tell me about your project..."></textarea>
            </div>
            <button type="submit" disabled={status === 'sending'}
              className="w-full py-4 bg-gradient-to-r from-green-500 to-gold-500 text-white font-bold rounded-lg hover:shadow-lg hover:shadow-green-500/30 transition-all transform hover:-translate-y-1 disabled:opacity-70 disabled:cursor-not-allowed">
              {status === 'sending' ? (
                <span><i className="fas fa-spinner fa-spin mr-2"></i>Sending...</span>
              ) : 'Send Message'}
            </button>
            {status === 'success' && (
              <div className="p-4 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400 text-center">
                <i className="fas fa-check-circle mr-2"></i>Message sent! I'll get back to you soon.
              </div>
            )}
            {status === 'error' && (
              <div className="p-4 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-center">
                <i className="fas fa-exclamation-circle mr-2"></i>Something went wrong. Please try again or email directly.
              </div>
            )}
          </form>
        </div>
      );
    }

    // Mount React components
    ReactDOM.render(<SkillBars />, document.getElementById('react-root'));
    ReactDOM.render(<ContactForm />, document.getElementById('contact-form-root'));
  </script>

    <script>
        // ===== HAMBURGER MENU =====
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobile-menu');
        const hamburgerIcon = document.getElementById('hamburger-icon');

        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
            hamburgerIcon.className = mobileMenu.classList.contains('open') ?
                'fas fa-times' : 'fas fa-bars';
        });

        document.querySelectorAll('.mobile-menu-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('open');
                hamburgerIcon.className = 'fas fa-bars';
            });
        });

        // ===== NAVBAR SCROLL EFFECT =====
        window.addEventListener('scroll', () => {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('shadow-xl');
            } else {
                navbar.classList.remove('shadow-xl');
            }
        });

        // ===== SMOOTH SCROLL OFFSET (for fixed nav) =====
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    const offset = 80;
                    const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({
                        top,
                        behavior: 'smooth'
                    });
                }
            });
        });
        // ===== THEME TOGGLE =====
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        function updateThemeIcon() {
            if (document.documentElement.classList.contains('dark')) {
                themeIcon.className = 'fas fa-sun';
            } else {
                themeIcon.className = 'fas fa-moon';
            }
        }

        updateThemeIcon();

        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcon();
        });
    </script>
    <script src="<?= htmlspecialchars($app_base_url) ?>/assets/js/optimize.min.js"></script>
</body>

</html>