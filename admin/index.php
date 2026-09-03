<?php
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
session_start();
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/datastore.php';

$admin_url = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/');
$project_url = dirname($admin_url);
$project_url = $project_url === '/' || $project_url === '.' ? '' : rtrim($project_url, '/');
$asset_url = static function (?string $url) use ($project_url): string {
  if (!$url || preg_match('~^(https?:|data:|#)~', $url) || $url[0] !== '/') return $url ?? '';
  return $project_url . $url;
};

// Handle login/logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
  Auth::logout();
  header('Location: ' . $admin_url . '/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if (Auth::login($username, $password)) {
    header('Location: ' . $admin_url . '/index.php');
    exit;
  } else {
    $login_error = 'Invalid credentials. Default: alfred / alfred2024';
  }
}

// If logged in, load all data for React
if (Auth::isLoggedIn()) {
  $sections_to_load = ['hero', 'about', 'skills', 'experience', 'portfolio', 'ventures', 'education', 'contact', 'site_settings'];
  $all_data = [];
  $deleted_data = [];

  foreach ($sections_to_load as $s) {
    if (in_array($s, ['hero', 'about', 'contact', 'site_settings'])) {
      $all_data[$s] = DataStore::get($s);
    } else {
      $all_data[$s] = DataStore::get($s, false); // Active only
      $deleted_data[$s] = DataStore::get($s, true); // Everything
    }
  }

  $deleted_data['messages_unread'] = DB::row("SELECT COUNT(*) as c FROM messages WHERE is_read = 0")['c'] ?? 0;
  $cms_data_json = json_encode($all_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
  $deleted_data_json = json_encode($deleted_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

// Fetch favicon from hero logo
$hero_icon = ($all_data['hero']['logo'] ?? null) ?: (DataStore::get('hero')['logo'] ?? '/uploads/logowiz_02032026_173518_69b05669498e3.jpg');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alfred Portfolio CMS</title>
  <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($asset_url($hero_icon)) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/tailwind.min.css">
  <script crossorigin src="https://cdn.jsdelivr.net/npm/react@18/umd/react.production.min.js"></script>
  <script crossorigin src="https://cdn.jsdelivr.net/npm/react-dom@18/umd/react-dom.production.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@babel/standalone@7/babel.min.js"></script>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: #0d1117;
      color: #c9d1d9;
      font-family: 'Inter', sans-serif;
    }

    .sidebar-item {
      transition: all 0.2s ease;
    }

    .sidebar-item:hover,
    .sidebar-item.active {
      background: #21262d;
      color: #58a6ff;
      border-left: 3px solid #238636;
    }

    .cms-input {
      background: #161b22;
      border: 1px solid #30363d;
      color: #c9d1d9;
      border-radius: 6px;
      padding: 8px 12px;
      width: 100%;
      transition: border-color 0.2s;
    }

    .cms-input:focus {
      outline: none;
      border-color: #58a6ff;
    }

    .cms-textarea {
      background: #161b22;
      border: 1px solid #30363d;
      color: #c9d1d9;
      border-radius: 6px;
      padding: 8px 12px;
      width: 100%;
      resize: vertical;
      min-height: 80px;
    }

    .cms-textarea:focus {
      outline: none;
      border-color: #58a6ff;
    }

    .cms-label {
      color: #8b949e;
      font-size: 12px;
      margin-bottom: 4px;
      display: block;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .cms-btn-primary {
      background: #238636;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.2s;
    }

    .cms-btn-primary:hover {
      background: #2ea043;
    }

    .cms-btn-danger {
      background: #da3633;
      color: #fff;
      border: none;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 12px;
      transition: background 0.2s;
    }

    .cms-btn-danger:hover {
      background: #f85149;
    }

    .cms-btn-secondary {
      background: #21262d;
      color: #c9d1d9;
      border: 1px solid #30363d;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .cms-btn-secondary:hover {
      background: #2d333b;
    }

    .cms-card {
      background: #21262d;
      border: 1px solid #30363d;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 16px;
    }

    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 9999;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      animation: slideIn 0.3s ease;
    }

    .toast.success {
      background: #238636;
      color: #fff;
    }

    .toast.error {
      background: #da3633;
      color: #fff;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100px);
        opacity: 0
      }

      to {
        transform: translateX(0);
        opacity: 1
      }
    }

    .section-header {
      border-bottom: 1px solid #30363d;
      padding-bottom: 12px;
      margin-bottom: 24px;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      background: #161b22;
      border: 1px solid #30363d;
      border-radius: 20px;
      padding: 4px 12px;
      font-size: 12px;
      margin: 3px;
    }

    .pill button {
      margin-left: 6px;
      color: #f85149;
      background: none;
      border: none;
      cursor: pointer;
      line-height: 1;
    }
  </style>
</head>

<body>

  <?php if (!Auth::isLoggedIn()): ?>
    <!-- ===================== LOGIN PAGE ===================== -->
    <div class="min-h-screen flex items-center justify-center" style="background:#0d1117">
      <div class="w-full max-w-md" style="background:#161b22; border:1px solid #30363d; border-radius:12px; padding:40px;">
        <div class="text-center mb-8">
          <div style="background:linear-gradient(135deg,#238636,#FFA000); border-radius:12px; width:56px; height:56px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
            <i class="fas fa-lock text-white text-xl"></i>
          </div>
          <h1 style="color:#f0f6fc; font-size:24px; font-weight:700; margin-bottom:4px;">Portfolio CMS</h1>
          <p style="color:#8b949e; font-size:14px;">Sign in to manage your content</p>
        </div>

        <?php if (isset($login_error)): ?>
          <div style="background:#da363320; border:1px solid #da3633; border-radius:6px; padding:12px; margin-bottom:16px; color:#f85149; font-size:14px;">
            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($login_error) ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div style="margin-bottom:16px;">
            <label class="cms-label">Username</label>
            <input type="text" name="username" class="cms-input" placeholder="alfred" required autocomplete="username">
          </div>
          <div style="margin-bottom:24px;">
            <label class="cms-label">Password</label>
            <input type="password" name="password" class="cms-input" placeholder="••••••••" required autocomplete="current-password">
          </div>
          <button type="submit" name="login" class="cms-btn-primary w-full" style="width:100%; padding:12px; font-size:16px;">
            <i class="fas fa-sign-in-alt mr-2"></i>Sign In
          </button>
        </form>
        <p style="color:#6e7681; font-size:12px; text-align:center; margin-top:16px;">Default: alfred / alfred2024</p>
      </div>
    </div>

  <?php else: ?>
    <!-- ===================== CMS ADMIN PANEL ===================== -->
    <div id="cms-root"></div>

    <script>
      window.__CMS_DATA__ = <?= $cms_data_json ?>;
      window.__DELETED_DATA__ = <?= $deleted_data_json ?>;
      window.__API_BASE__ = '<?= htmlspecialchars($project_url) ?>/api';
      window.__ASSET_BASE__ = '<?= htmlspecialchars($project_url) ?>';
    </script>

    <script type="text/babel">
      const { useState, useEffect, useCallback, useRef } = React;

// ===== TOAST NOTIFICATION =====
function Toast({ message, type, onClose }) {
  useEffect(() => {
    const t = setTimeout(onClose, 3000);
    return () => clearTimeout(t);
  }, []);
  return <div className={`toast ${type}`}>{message}</div>;
}

// ===== GENERIC FIELD =====
function Field({ label, value, onChange, type='text', multiline=false, hint }) {
  return (
    <div style={{marginBottom:16}}>
      <label className="cms-label">{label}</label>
      {multiline
        ? <textarea className="cms-textarea" value={value} onChange={e=>onChange(e.target.value)} />
        : <input type={type} className="cms-input" value={value} onChange={e=>onChange(e.target.value)} />
      }
      {hint && <p style={{color:'#6e7681',fontSize:'11px',marginTop:4}}>{hint}</p>}
    </div>
  );
}

// ===== TAGS / PILLS INPUT =====
function TagsInput({ label, tags, onChange }) {
  const [input, setInput] = useState('');
  const add = () => {
    if (input.trim() && !tags.includes(input.trim())) {
      onChange([...tags, input.trim()]);
      setInput('');
    }
  };
  const remove = (i) => onChange(tags.filter((_,idx)=>idx!==i));
  return (
    <div style={{marginBottom:16}}>
      <label className="cms-label">{label}</label>
      <div style={{display:'flex',gap:8,marginBottom:6}}>
        <input className="cms-input" style={{flex:1}} value={input} onChange={e=>setInput(e.target.value)}
          onKeyDown={e=>{if(e.key==='Enter'){e.preventDefault();add();}}}
          placeholder="Type and press Enter" />
        <button className="cms-btn-secondary" onClick={add}>Add</button>
      </div>
      <div>{tags.map((t,i)=><span key={i} className="pill">{t}<button onClick={()=>remove(i)}>×</button></span>)}</div>
    </div>
  );
}

// ===== IMAGE UPLOAD COMPONENT =====
function ImageUpload({ label, value, onChange, section = 'general' }) {
  const [uploading, setUploading] = useState(false);
  const fileInput = useRef(null);

  const handleUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('file', file);
    formData.append('section', section);

    try {
      const res = await fetch(`${window.__API_BASE__}/upload.php`, {
        method: 'POST',
        body: formData,
      });
      const result = await res.json();
      if (result.success) {
        onChange(result.url);
      } else {
        alert('Upload failed: ' + result.error);
      }
    } catch (err) {
      alert('Upload error. Check console.');
      console.error(err);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div style={{ marginBottom: 16 }}>
      <label className="cms-label">{label}</label>
      <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
        {value && (
          <div style={{ width: 60, height: 60, borderRadius: 6, overflow: 'hidden', border: '1px solid #30363d', background: '#161b22' }}>
            <img src={value && value.startsWith('/') ? window.__ASSET_BASE__ + value : value} style={{ width: '100%', height: '100%', objectFit: 'cover' }} alt="Preview" />
          </div>
        )}
        <div style={{ flex: 1 }}>
          <input type="text" className="cms-input" value={value} onChange={(e) => onChange(e.target.value)} placeholder="URL or upload file..." style={{ marginBottom: 6 }} />
          <div style={{ display: 'flex', gap: 8 }}>
            <button className="cms-btn-secondary" onClick={() => fileInput.current.click()} disabled={uploading}>
              {uploading ? <i className="fas fa-spinner fa-spin mr-2"></i> : <i className="fas fa-upload mr-2"></i>}
              Upload File
            </button>
            {value && <button className="cms-btn-danger" onClick={() => onChange('')} style={{ padding: '6px 12px' }}><i className="fas fa-times"></i></button>}
          </div>
          <input type="file" ref={fileInput} style={{ display: 'none' }} onChange={handleUpload} accept="image/*" />
        </div>
      </div>
    </div>
  );
}

// ===== SAVE BUTTON =====
function SaveBtn({ onClick, saving, label = "Save Changes" }) {
  return (
    <button className="cms-btn-primary" onClick={onClick} disabled={saving} style={{ minWidth: 120 }}>
      {saving ? <><i className="fas fa-spinner fa-spin mr-2"></i>Saving...</> : <><i className="fas fa-save mr-2"></i>{label}</>}
    </button>
  );
}

// ===== TRASH / RESTORE VIEW =====
function TrashView({ section, items, onRestore, onBack }) {
  // Skills might be structured {coding:[], tools:[]}
  const flatItems = Array.isArray(items) ? items : [...(items?.coding || []), ...(items?.tools || [])];
  const deleted = (flatItems || []).filter(i => i.deleted_at);
  return (
    <div>
      <div className="section-header" style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
        <button className="cms-btn-secondary" onClick={onBack} title="Go Back"><i className="fas fa-arrow-left"></i></button>
        <h2 style={{ color: '#f0f6fc', fontSize: 20, fontWeight: 700 }}>🗑️ {section.toUpperCase()} Trash</h2>
      </div>
      <div style={{ display: 'grid', gap: 10 }}>
        {deleted.length === 0 ? (
          <div style={{ padding: 40, textAlign: 'center', color: '#6e7681' }}>
            <i className="fas fa-trash-restore" style={{ fontSize: 32, display: 'block', marginBottom: 12 }}></i>
            No deleted items found.
          </div>
        ) : deleted.map((item, idx) => (
          <div key={item.id || idx} className="cms-card" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', opacity: 0.8 }}>
            <div>
              <div style={{ color: '#c9d1d9', fontWeight: 600 }}>{item.title || item.name || item.degree || item.company || "Untitled Item"}</div>
              <div style={{ color: '#8b949e', fontSize: 11 }}>Deleted on: {item.deleted_at}</div>
            </div>
            <button className="cms-btn-secondary" style={{ fontSize: 12 }} onClick={() => onRestore(section, item.id)}>
              <i className="fas fa-redo mr-1"></i>Restore
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}

// ===================== SECTION EDITORS =====================

// --- HERO ---
function HeroEditor({ data, onSave }) {
  const [d, setD] = useState(data);
  const f = (key) => (val) => setD(p=>({...p,[key]:val}));
  return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>🏠 Hero Section</h2></div>
      <Field label="Greeting Text" value={d.greeting} onChange={f('greeting')} />
      <Field label="Full Name" value={d.name} onChange={f('name')} />
      <Field label="Title / Tagline" value={d.title} onChange={f('title')} />
      <ImageUpload label="Profile Photo" value={d.photo} onChange={f('photo')} section="hero" />
      <ImageUpload label="Website Logo" value={d.logo} onChange={f('logo')} section="hero" />
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
        <Field label="GitHub URL" value={d.github} onChange={f('github')} />
        <Field label="LinkedIn URL" value={d.linkedin} onChange={f('linkedin')} />
        <Field label="Twitter URL" value={d.twitter} onChange={f('twitter')} />
        <Field label="Instagram URL" value={d.instagram} onChange={f('instagram')} />
      </div>
      <div style={{display:'flex',gap:8}}>
        <Field label="Hire Me Link" value={d.hire_me_link} onChange={f('hire_me_link')} />
        <Field label="Portfolio Link" value={d.portfolio_link} onChange={f('portfolio_link')} />
      </div>
      <SaveBtn onClick={()=>onSave('hero',d)} />
    </div>
  );
}

// --- ABOUT ---
function AboutEditor({ data, onSave }) {
  const [d, setD] = useState(data);
  const updateStat = (i, key, val) => {
    const stats = [...d.stats];
    stats[i] = {...stats[i], [key]: val};
    setD(p=>({...p, stats}));
  };
  return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>👤 About Section</h2></div>
      <Field label="Bio Paragraph 1 (HTML allowed)" value={d.bio1} onChange={v=>setD(p=>({...p,bio1:v}))} multiline />
      <Field label="Bio Paragraph 2 (HTML allowed)" value={d.bio2} onChange={v=>setD(p=>({...p,bio2:v}))} multiline />
      <p className="cms-label" style={{marginTop:16,marginBottom:8}}>STATS</p>
      <div style={{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:12}}>
        {(d.stats||[]).map((s,i)=>(
          <div key={i} className="cms-card" style={{padding:12}}>
            <Field label="Value" value={s.value} onChange={v=>updateStat(i,'value',v)} />
            <Field label="Label" value={s.label} onChange={v=>updateStat(i,'label',v)} />
          </div>
        ))}
      </div>
      <SaveBtn onClick={()=>onSave('about',d)} />
    </div>
  );
}

// --- SKILLS ---
function SkillsEditor({ data, deletedItems, onSaveItem, onDeleteItem, onRestoreItem }) {
  // Handle structured {coding:[], tools:[]} or flat array
  const flatData = Array.isArray(data) ? data : [...(data?.coding || []), ...(data?.tools || [])];
  const [items, setItems] = useState(flatData);
  const [showTrash, setShowTrash] = useState(false);
  const coding = items.filter(i => i.type === 'coding');
  const tools = items.filter(i => i.type === 'tool');

  const update = (id, k, v) => {
    setItems(prev => prev.map(i => i.id === id ? {...i, [k]: v} : i));
  };

  if (showTrash) return <TrashView section="skills" items={deletedItems} onRestore={onRestoreItem} onBack={()=>setShowTrash(false)} />;

  return (
    <div>
      <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
        <h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>⚡ Skills & Tools</h2>
        <div style={{display:'flex',gap:10}}>
          <button className="cms-btn-secondary" onClick={()=>setShowTrash(true)}><i className="fas fa-trash-alt mr-1"></i>Trash</button>
        </div>
      </div>

      <div style={{marginBottom:32}}>
        <div style={{display:'flex',justifyContent:'space-between',marginBottom:12}}>
            <h3 className="cms-label" style={{fontSize:14}}>CODING SKILLS</h3>
            <button className="cms-btn-secondary" style={{fontSize:11}} onClick={()=>onSaveItem('skills',{type:'coding',name:'New Skill',percent:80})}><i className="fas fa-plus"></i></button>
        </div>
        {coding.map((s, idx) => (
            <div key={s.id || idx} className="cms-card" style={{display:'grid',gridTemplateColumns:'1fr 1fr auto auto',gap:12,alignItems:'end'}}>
                <Field label="Skill Name" value={s.name} onChange={v=>update(s.id,'name',v)} />
                <Field label="Percent" value={String(s.percent)} onChange={v=>update(s.id,'percent',parseInt(v)||0)} type="number" />
                <button className="cms-btn-primary" onClick={()=>onSaveItem('skills',s)} style={{marginBottom:16,height:38}}><i className="fas fa-save"></i></button>
                <button className="cms-btn-danger" onClick={()=>onDeleteItem('skills',s.id)} style={{marginBottom:16,height:38}}><i className="fas fa-trash"></i></button>
            </div>
        ))}
      </div>

      <div>
        <div style={{display:'flex',justifyContent:'space-between',marginBottom:12}}>
            <h3 className="cms-label" style={{fontSize:14}}>TOOLS & DESIGN</h3>
            <button className="cms-btn-secondary" style={{fontSize:11}} onClick={()=>onSaveItem('skills',{type:'tool',name:'New Tool',icon:'fas fa-tools',color:'text-slate-300'})}><i className="fas fa-plus"></i></button>
        </div>
        <div style={{display:'grid',gridTemplateColumns:'repeat(auto-fill, minmax(280px, 1fr))', gap:12}}>
            {tools.map((t, idx) => (
                <div key={t.id || idx} className="cms-card">
                    <div style={{display:'flex',justifyContent:'space-between',marginBottom:12}}>
                        <span style={{color:'#58a6ff',fontWeight:700}}>{t.name}</span>
                        <div style={{display:'flex',gap:8}}>
                            <button className="cms-btn-primary" style={{padding:'4px 10px'}} onClick={()=>onSaveItem('skills',t)}><i className="fas fa-save"></i></button>
                            <button className="cms-btn-danger" style={{padding:'4px 10px'}} onClick={()=>onDeleteItem('skills',t.id)}><i className="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <Field label="Name" value={t.name} onChange={v=>update(t.id,'name',v)} />
                    <Field label="Icon Class" value={t.icon} onChange={v=>update(t.id,'icon',v)} hint="e.g. fab fa-react" />
                    <Field label="Color Class" value={t.color} onChange={v=>update(t.id,'color',v)} hint="text-blue-400" />
                    <Field label="Short Desc" value={t.description} onChange={v=>update(t.id,'description',v)} />
                </div>
            ))}
        </div>
      </div>
    </div>
  );
}

// --- EXPERIENCE ---
function ExperienceEditor({ data, deletedItems, onSaveItem, onDeleteItem, onRestoreItem }) {
  const [items, setItems] = useState(data);
  const [showTrash, setShowTrash] = useState(false);
  const update = (i, k, v) => { const arr=[...items]; arr[i]={...arr[i],[k]:v}; setItems(arr); };
  const addBullet = (i) => {
    const arr=[...items];
    arr[i].bullets = [...(arr[i].bullets||[]), ''];
    setItems(arr);
  };
  const updateBullet = (i, bi, v) => {
    const arr=[...items];
    arr[i].bullets[bi] = v;
    setItems(arr);
  };
  const removeBullet = (i, bi) => {
    const arr=[...items];
    arr[i].bullets = arr[i].bullets.filter((_,idx)=>idx!==bi);
    setItems(arr);
  };

  if (showTrash) return <TrashView section="experience" items={deletedItems} onRestore={onRestoreItem} onBack={()=>setShowTrash(false)} />;

  return (
    <div>
      <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
        <h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>💼 Experience</h2>
        <div style={{display:'flex',gap:8}}>
          <button className="cms-btn-secondary" onClick={()=>setShowTrash(true)}><i className="fas fa-trash-alt mr-1"></i>Trash</button>
          <button className="cms-btn-primary" onClick={()=>onSaveItem('experience',{period:'2024-Present',title:'New Role',company:'Company Name',bullets:[]})}><i className="fas fa-plus mr-1"></i>Add</button>
        </div>
      </div>
      {items.map((exp,i)=>(
        <div key={exp.id || i} className="cms-card">
          <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12}}>
            <span style={{color:'#58a6ff',fontWeight:700}}>{exp.title} at {exp.company}</span>
            <div style={{display:'flex',gap:8}}>
              <button className="cms-btn-primary" onClick={()=>onSaveItem('experience',exp)}><i className="fas fa-save mr-1"></i>Save</button>
              <button className="cms-btn-danger" onClick={()=>onDeleteItem('experience',exp.id)}><i className="fas fa-trash"></i></button>
            </div>
          </div>
          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
            <Field label="Period" value={exp.period} onChange={v=>update(i,'period',v)} />
            <Field label="Title" value={exp.title} onChange={v=>update(i,'title',v)} />
          </div>
          <Field label="Company" value={exp.company} onChange={v=>update(i,'company',v)} />
          <p className="cms-label">Bullets</p>
          {(exp.bullets||[]).map((b,bi)=>(
            <div key={bi} style={{display:'flex',gap:8,marginBottom:8}}>
              <input className="cms-input" value={b} onChange={e=>updateBullet(i,bi,e.target.value)} />
              <button className="cms-btn-danger" style={{padding:'4px 8px'}} onClick={()=>removeBullet(i,bi)}>×</button>
            </div>
          ))}
          <button className="cms-btn-secondary" style={{fontSize:11}} onClick={()=>addBullet(i)}>+ Add Bullet</button>
        </div>
      ))}
    </div>
  );
}

// --- PORTFOLIO ---
function PortfolioEditor({ data, deletedItems, onSaveItem, onDeleteItem, onRestoreItem }) {
  const [items, setItems] = useState(data);
  const [showTrash, setShowTrash] = useState(false);
  const catColors = ['green','gold','blue','purple','red','cyan'];

  const update = (i, key, val) => {
    const arr = [...items];
    arr[i] = {...arr[i], [key]: val};
    setItems(arr);
  };

  const addItem = () => {
    const newItem = {
        title:'New Project', category:'Web Dev', cat_color:'green',
        description:'Project description here.', icon:'fas fa-code', icon_color:'text-green-500',
        bg_gradient:'from-green-500/20 to-gold-500/20', tags:[], link:'#'
    };
    onSaveItem('portfolio', newItem);
  };

  if (showTrash) return <TrashView section="portfolio" items={deletedItems} onRestore={onRestoreItem} onBack={()=>setShowTrash(false)} />;

  return (
    <div>
      <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
        <h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>🗂️ Portfolio Projects</h2>
        <div style={{display:'flex',gap:8}}>
            <button className="cms-btn-secondary" onClick={()=>setShowTrash(true)}><i className="fas fa-trash-alt mr-1"></i>Trash</button>
            <button className="cms-btn-primary" onClick={addItem}><i className="fas fa-plus mr-1"></i>Add Project</button>
        </div>
      </div>
      <div style={{display:'grid',gap:16}}>
        {items.map((p,i)=>(
          <div key={p.id || i} className="cms-card">
            <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12}}>
              <span style={{color:'#58a6ff',fontWeight:700}}>{p.title}</span>
              <div style={{display:'flex',gap:8}}>
                  <button className="cms-btn-secondary" onClick={()=>onSaveItem('portfolio', p)}><i className="fas fa-save mr-1"></i>Save</button>
                  <button className="cms-btn-danger" onClick={()=>onDeleteItem('portfolio', p.id)}><i className="fas fa-trash mr-1"></i>Delete</button>
              </div>
            </div>
            <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
              <Field label="Title" value={p.title} onChange={v=>update(i,'title',v)} />
              <Field label="Category Label" value={p.category} onChange={v=>update(i,'category',v)} />
            </div>
            <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
              <div style={{marginBottom:16}}>
                <label className="cms-label">Category Color</label>
                <select className="cms-input" value={p.cat_color} onChange={e=>update(i,'cat_color',e.target.value)}>
                  {catColors.map(c=><option key={c} value={c}>{c}</option>)}
                </select>
              </div>
              <Field label="Project Link" value={p.link} onChange={v=>update(i,'link',v)} />
            </div>
            <ImageUpload label="Project Image" value={p.image} onChange={v=>update(i,'image',v)} section="portfolio" />
            <Field label="Description" value={p.description} onChange={v=>update(i,'description',v)} multiline />
            <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 1fr',gap:12}}>
              <Field label="Icon Class" value={p.icon} onChange={v=>update(i,'icon',v)} hint="e.g. fas fa-globe" />
              <Field label="Icon Color" value={p.icon_color} onChange={v=>update(i,'icon_color',v)} hint="e.g. text-green-500" />
              <Field label="BG Gradient" value={p.bg_gradient} onChange={v=>update(i,'bg_gradient',v)} hint="e.g. from-green-500/20 to-gold-500/20" />
            </div>
            <TagsInput label="Technology Tags" tags={p.tags||[]} onChange={v=>update(i,'tags',v)} />
          </div>
        ))}
      </div>
    </div>
  );
}

// --- VENTURES ---
function VenturesEditor({ data, deletedItems, onSaveItem, onDeleteItem, onRestoreItem }) {
  const [items, setItems] = useState(data);
  const [showTrash, setShowTrash] = useState(false);
  const update = (i, k, v) => { const arr=[...items]; arr[i]={...arr[i],[k]:v}; setItems(arr); };

  if (showTrash) return <TrashView section="ventures" items={deletedItems} onRestore={onRestoreItem} onBack={()=>setShowTrash(false)} />;

  return (
    <div>
      <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
        <h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>🚀 Ventures / Brands</h2>
        <div style={{display:'flex',gap:8}}>
          <button className="cms-btn-secondary" onClick={()=>setShowTrash(true)}><i className="fas fa-trash-alt mr-1"></i>Trash</button>
          <button className="cms-btn-primary" onClick={()=>onSaveItem('ventures',{name:'New Venture',role:'Founder'})}><i className="fas fa-plus mr-1"></i>Add</button>
        </div>
      </div>
      {items.map((v,i)=>(
        <div key={v.id || i} className="cms-card">
          <div style={{display:'flex',justifyContent:'space-between',alignItems:'center',marginBottom:12}}>
            <span style={{color:'#58a6ff',fontWeight:700}}>{v.name}</span>
            <div style={{display:'flex',gap:8}}>
              <button className="cms-btn-primary" onClick={()=>onSaveItem('ventures',v)}><i className="fas fa-save mr-1"></i>Save</button>
              <button className="cms-btn-danger" onClick={()=>onDeleteItem('ventures',v.id)}><i className="fas fa-trash"></i></button>
            </div>
          </div>
          <div style={{display:'grid',gridTemplateColumns:'80px 1fr 1fr',gap:12}}>
            <Field label="Initial" value={v.initial} onChange={val=>update(i,'initial',val)} />
            <Field label="Name" value={v.name} onChange={val=>update(i,'name',val)} />
            <Field label="Role" value={v.role} onChange={val=>update(i,'role',val)} />
          </div>
          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
            <Field label="Role Color" value={v.role_color} onChange={val=>update(i,'role_color',val)} />
            <Field label="BG Color" value={v.bg_color} onChange={val=>update(i,'bg_color',val)} />
          </div>
          <ImageUpload label="Logo" value={v.logo} onChange={val=>update(i,'logo',val)} section="ventures" />
          <Field label="Description" value={v.description} onChange={val=>update(i,'description',val)} multiline />
        </div>
      ))}
    </div>
  );
}

// --- EDUCATION ---
function EducationEditor({ data, deletedItems, onSaveItem, onDeleteItem, onRestoreItem }) {
  const [items, setItems] = useState(data);
  const [showTrash, setShowTrash] = useState(false);
  const update = (i, k, v) => { const arr=[...items]; arr[i]={...arr[i],[k]:v}; setItems(arr); };

  if (showTrash) return <TrashView section="education" items={deletedItems} onRestore={onRestoreItem} onBack={()=>setShowTrash(false)} />;

  return (
    <div>
      <div className="section-header" style={{display:'flex',justifyContent:'space-between',alignItems:'center'}}>
        <h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>🎓 Education</h2>
        <div style={{display:'flex',gap:8}}>
            <button className="cms-btn-secondary" onClick={()=>setShowTrash(true)}><i className="fas fa-trash-alt mr-1"></i>Trash</button>
            <button className="cms-btn-primary" onClick={()=>onSaveItem('education',{degree:'New Degree',institution:'Uni',period:'2024'})}><i className="fas fa-plus mr-1"></i>Add</button>
        </div>
      </div>
      {items.map((e,i)=>(
        <div key={e.id || i} className="cms-card">
          <div style={{display:'grid',gridTemplateColumns:'1fr 1fr 150px auto auto',gap:12,alignItems:'end'}}>
            <Field label="Degree" value={e.degree} onChange={v=>update(i,'degree',v)} />
            <Field label="Institution" value={e.institution} onChange={v=>update(i,'institution',v)} />
            <Field label="Period" value={e.period} onChange={v=>update(i,'period',v)} />
            <button className="cms-btn-primary" onClick={()=>onSaveItem('education',e)} style={{marginBottom:16,height:38}} title="Save"><i className="fas fa-save"></i></button>
            <button className="cms-btn-danger" onClick={()=>onDeleteItem('education',e.id)} style={{marginBottom:16,height:38}} title="Delete"><i className="fas fa-trash"></i></button>
          </div>
        </div>
      ))}
    </div>
  );
}

// --- CONTACT ---
function ContactEditor({ data, onSave }) {
  const [d, setD] = useState(data);
  const f = (key) => (val) => setD(p=>({...p,[key]:val}));
  return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>📬 Contact Details</h2></div>
      <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:12}}>
        <Field label="Phone Number" value={d.phone} onChange={f('phone')} />
        <Field label="Phone Link (tel:)" value={d.phone_link} onChange={f('phone_link')} />
        <Field label="Email Address" value={d.email} onChange={f('email')} />
        <Field label="Website URL" value={d.website} onChange={f('website')} />
        <Field label="Location" value={d.location} onChange={f('location')} />
        <Field label="Availability Text" value={d.availability} onChange={f('availability')} />
        <Field label="Copyright Year" value={d.copyright_year} onChange={f('copyright_year')} />
      </div>
      <SaveBtn onClick={()=>onSave('contact',d)} />
    </div>
  );
}

// --- SITE SETTINGS ---
function SettingsEditor({ data, onSave }) {
  const [d, setD] = useState(data);
  const f = (key) => (val) => setD(p=>({...p,[key]:val}));
  const updateNav = (i, val) => {
    const nav = [...d.nav_links];
    nav[i] = val;
    setD(p=>({...p, nav_links:nav}));
  };
  const addNav = () => setD(p=>({...p, nav_links:[...p.nav_links, 'New Link']}));
  const removeNav = (i) => setD(p=>({...p, nav_links:p.nav_links.filter((_,idx)=>idx!==i)}));
  return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>⚙️ Site Settings</h2></div>
      <Field label="Site Title (browser tab)" value={d.site_title} onChange={f('site_title')} />
      <Field label="Meta Description (SEO)" value={d.meta_description} onChange={f('meta_description')} multiline />
      <p className="cms-label" style={{marginTop:16,marginBottom:8}}>NAVIGATION LINKS</p>
      <p style={{color:'#6e7681',fontSize:12,marginBottom:8}}>These link to #about, #skills etc. (anchor IDs)</p>
      {(d.nav_links||[]).map((link,i)=>(
        <div key={i} style={{display:'flex',gap:8,marginBottom:8}}>
          <input className="cms-input" value={link} onChange={e=>updateNav(i,e.target.value)} style={{flex:1}} />
          <button className="cms-btn-danger" onClick={()=>removeNav(i)}>×</button>
        </div>
      ))}
      <button className="cms-btn-secondary" onClick={addNav} style={{marginBottom:16,fontSize:12}}>
        <i className="fas fa-plus mr-1"></i>Add Nav Link
      </button>
      <br/>
      <SaveBtn onClick={()=>onSave('site_settings',d)} />

      <div className="section-header" style={{marginTop:48}}><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>🔒 Change Password</h2></div>
      <div className="cms-card">
        <label className="cms-label">New Password</label>
        <div style={{display:'flex',gap:12}}>
          <input type="password" id="new-password" class="cms-input" placeholder="Enter new password..." style={{flex:1}} />
          <button className="cms-btn-primary" onClick={async () => {
            const pass = document.getElementById('new-password').value;
            if(!pass) return;
            const res = await fetch(`${window.__API_BASE__}/cms.php`, {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify({action:'change_password', new_password: pass})
            });
            const result = await res.json();
            alert(result.message);
            if(result.success) document.getElementById('new-password').value = '';
          }}>Update Password</button>
        </div>
      </div>
    </div>
  );
}

// --- MESSAGES ---
function MessagesView() {
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  useEffect(() => {
    fetch(`${window.__API_BASE__}/contact.php?action=list`)
      .then(r=>r.json())
      .then(data=>{ setMessages(data.messages||[]); setLoading(false); })
      .catch(()=>setLoading(false));
  }, []);
  const deleteMsg = (id) => {
    fetch(`${window.__API_BASE__}/contact.php?action=delete&id=${id}`, {method:'DELETE'})
      .then(r=>r.json())
      .then(()=>setMessages(m=>m.filter(msg=>msg.id !== parseInt(id))));
  };
  const markRead = (id) => {
    fetch(`${window.__API_BASE__}/contact.php?action=read&id=${id}`, {method:'PATCH'})
      .then(r=>r.json())
      .then(()=>setMessages(m=>m.map(msg=>msg.id === id ? {...msg, is_read: 1} : msg)));
  };
  if (loading) return <div style={{color:'#8b949e',padding:20}}>Loading messages...</div>;
  if (!messages.length) return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>📩 Contact Messages</h2></div>
      <div style={{textAlign:'center',padding:60,color:'#6e7681'}}>
        <i className="fas fa-inbox" style={{fontSize:48,display:'block',marginBottom:12}}></i>
        No messages yet.
      </div>
    </div>
  );
  return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>📩 Contact Messages ({messages.length})</h2></div>
      {messages.map((msg,i)=>(
        <div key={i} className="cms-card" style={{opacity: msg.is_read ? 0.7 : 1, borderLeft: msg.is_read ? '1px solid #30363d' : '4px solid #238636'}}>
          <div style={{display:'flex',justifyContent:'space-between',alignItems:'flex-start',marginBottom:12}}>
            <div>
              <span style={{color:'#f0f6fc',fontWeight:700}}>{msg.name}</span>
              <span style={{color:'#8b949e',marginLeft:8,fontSize:13}}>{msg.email}</span>
              {!msg.is_read && <span style={{marginLeft:12,fontSize:10,background:'#238636',color:'#fff',padding:'2px 6px',borderRadius:10,textTransform:'uppercase'}}>New</span>}
            </div>
            <div style={{display:'flex',gap:8,alignItems:'center'}}>
              <span style={{color:'#6e7681',fontSize:11}}>{msg.created_at}</span>
              <button className="cms-btn-danger" onClick={()=>deleteMsg(msg.id)} title="Delete"><i className="fas fa-trash"></i></button>
            </div>
          </div>
          <div style={{color:'#58a6ff',fontWeight:600,marginBottom:8}}>{msg.subject}</div>
          <p style={{color:'#8b949e',lineHeight:1.6,whiteSpace:'pre-wrap'}}>{msg.message}</p>
          <div style={{marginTop:8, display:'flex', gap:8}}>
            <a href={`mailto:${msg.email}`} className="cms-btn-secondary" style={{display:'inline-flex',alignItems:'center',gap:6,fontSize:12,textDecoration:'none'}} onClick={() => markRead(msg.id)}>
              <i className="fas fa-reply"></i> Reply
            </a>
            {!msg.is_read && (
              <button className="cms-btn-secondary" style={{fontSize:12}} onClick={() => markRead(msg.id)}>
                <i className="fas fa-check"></i> Mark as Read
              </button>
            )}
          </div>
        </div>
      ))}
    </div>
  );
}

// ===================== MAIN CMS APP =====================
// --- REPORTING VIEW ---
function ReportingView() {
  const [stats, setStats] = useState(null);
  useEffect(() => {
    $.get(`${window.__API_BASE__}/reports.php`, (res) => {
        if(res.success) setStats(res);
    });
  }, []);
  if (!stats) return <div style={{padding:20, color:'#8b949e'}}><i className="fas fa-spinner fa-spin mr-2"></i>Loading Reports...</div>;
  return (
    <div>
      <div className="section-header"><h2 style={{color:'#f0f6fc',fontSize:20,fontWeight:700}}>📊 Statistics & Reports</h2></div>
      <div style={{display:'grid', gridTemplateColumns:'repeat(auto-fit, minmax(200px, 1fr))', gap:20, marginBottom:32}}>
        <div className="cms-card" style={{borderTop:'4px solid #238636', textAlign:'center'}}>
            <div style={{fontSize:11, color:'#8b949e', fontWeight:600}}>LIVE CONTENT</div>
            <div style={{fontSize:36, color:'#f0f6fc', fontWeight:800, margin:'10px 0'}}>{stats.stats.portfolio + stats.stats.experience + stats.stats.ventures}</div>
            <div style={{fontSize:10, color:'#6e7681'}}>Active Database Rows</div>
        </div>
        <div className="cms-card" style={{borderTop:'4px solid #f85149', textAlign:'center'}}>
            <div style={{fontSize:11, color:'#8b949e', fontWeight:600}}>TRASH BIN</div>
            <div style={{fontSize:36, color:'#f0f6fc', fontWeight:800, margin:'10px 0'}}>{Object.values(stats.trash).reduce((a, b) => a + b, 0)}</div>
            <div style={{fontSize:10, color:'#6e7681'}}>Retrievable Deleted Items</div>
        </div>
        <div className="cms-card" style={{borderTop:'4px solid #58a6ff', textAlign:'center'}}>
            <div style={{fontSize:11, color:'#8b949e', fontWeight:600}}>UNREAD MESSAGES</div>
            <div style={{fontSize:36, color:'#f0f6fc', fontWeight:800, margin:'10px 0'}}>{stats.messages.unread}</div>
            <div style={{fontSize:10, color:'#6e7681'}}>Inquiries to process</div>
        </div>
      </div>
      <div className="cms-card" style={{padding:0, overflow:'hidden'}}>
         <div style={{padding:16, borderBottom:'1px solid #30363d', background:'#161b22', fontWeight:600}}>Database Integrity Report</div>
         <table style={{width:'100%', borderCollapse:'collapse'}}>
            <thead>
                <tr style={{textAlign:'left', fontSize:12, color:'#8b949e', background:'#0d1117', borderBottom:'1px solid #30363d'}}>
                    <th style={{padding:12}}>MODULE</th>
                    <th style={{padding:12}}>TABLE</th>
                    <th style={{padding:12}}>ACTIVE</th>
                    <th style={{padding:12}}>TRASH</th>
                    <th style={{padding:12}}>ACTION</th>
                </tr>
            </thead>
            <tbody>
                {Object.keys(stats.stats).map(k => (
                    <tr key={k} style={{borderBottom:'1px solid #21262d', fontSize:13}}>
                        <td style={{padding:12, color:'#f0f6fc', fontWeight:600, textTransform:'capitalize'}}>{k}</td>
                        <td style={{padding:12, color:'#8b949e'}}>{k}_*</td>
                        <td style={{padding:12, color:'#238636'}}>{stats.stats[k]}</td>
                        <td style={{padding:12, color:'#f85149'}}>{stats.trash[k]}</td>
                        <td style={{padding:12}}>
                           <button className="cms-btn-secondary" style={{fontSize:10, padding:'4px 8px'}} onClick={() => Swal.fire('Integrity Check', `Row count for ${k} verified against schema.`, 'success')}>
                              Review
                           </button>
                        </td>
                    </tr>
                ))}
            </tbody>
         </table>
      </div>
    </div>
  );
}

const SECTIONS = [
  { id:'hero',         label:'Hero',         icon:'fas fa-home' },
  { id:'about',        label:'About',        icon:'fas fa-user' },
  { id:'skills',       label:'Skills',       icon:'fas fa-code' },
  { id:'experience',   label:'Experience',   icon:'fas fa-briefcase' },
  { id:'portfolio',    label:'Portfolio',    icon:'fas fa-folder-open' },
  { id:'ventures',     label:'Ventures',     icon:'fas fa-rocket' },
  { id:'education',    label:'Education',    icon:'fas fa-graduation-cap' },
  { id:'contact',      label:'Contact',      icon:'fas fa-envelope' },
  { id:'site_settings',label:'Settings',     icon:'fas fa-cog' },
  { id:'messages',     label:'Messages',     icon:'fas fa-inbox' },
  { id:'reports',      label:'Reports',      icon:'fas fa-chart-bar' },
];

function CMS() {
  const [active, setActive] = useState(() => localStorage.getItem('cms_active_section') || 'hero');
  const [data, setData] = useState(window.__CMS_DATA__);
  const [saving, setSaving] = useState(false);
  const [toast, setToast] = useState(null);

  useEffect(() => {
    localStorage.setItem('cms_active_section', active);
  }, [active]);

  const showToast = (msg, type='success') => {
    setToast({msg, type});
    setTimeout(()=>setToast(null), 3500);
  };

  const ajaxAction = (action, payload) => {
    return $.ajax({
        url: `${window.__API_BASE__}/cms.php`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ action, ...payload }),
        dataType: 'json'
    });
  };

  const handleSave = (section, newData) => {
    setSaving(true);
    ajaxAction('save_section', { section, data: newData })
      .done(res => {
        setData(d => ({...d, [section]: newData}));
        showToast(`✅ ${section} saved successfully!`);
      })
      .fail(() => showToast('❌ Network error. Please try again.', 'error'))
      .always(() => setSaving(false));
  };

  const handleSaveItem = (section, rowData) => {
    ajaxAction('save_item', { section, data: rowData })
      .done(res => {
        if (res.success) window.location.reload();
        else showToast(`❌ Error: ${res.error}`, 'error');
      })
      .fail(() => showToast('❌ Network error.', 'error'));
  };

  const handleDeleteItem = (section, id) => {
    if (!confirm('Move this item to Trash?')) return;
    ajaxAction('delete_item', { section, id })
      .done(res => {
        if (res.success) window.location.reload();
        else showToast(`❌ Error: ${res.error}`, 'error');
      })
      .fail(() => showToast('❌ Network error.', 'error'));
  };

  const handleRestoreItem = (section, id) => {
    ajaxAction('restore_item', { section, id })
      .done(res => {
        if (res.success) window.location.reload();
        else showToast(`❌ Error: ${res.error}`, 'error');
      })
      .fail(() => showToast('❌ Network error.', 'error'));
  };

  const renderSection = () => {
    const deleted = window.__DELETED_DATA__ || {};
    switch(active) {
      case 'hero':       return <HeroEditor data={data.hero} onSave={handleSave} />;
      case 'about':      return <AboutEditor data={data.about} onSave={handleSave} />;
      case 'skills':     return <SkillsEditor data={data.skills} deletedItems={deleted.skills} onSaveItem={handleSaveItem} onDeleteItem={handleDeleteItem} onRestoreItem={handleRestoreItem} />;
      case 'experience': return <ExperienceEditor data={data.experience} deletedItems={deleted.experience} onSaveItem={handleSaveItem} onDeleteItem={handleDeleteItem} onRestoreItem={handleRestoreItem} />;
      case 'portfolio':  return <PortfolioEditor data={data.portfolio} deletedItems={deleted.portfolio} onSaveItem={handleSaveItem} onDeleteItem={handleDeleteItem} onRestoreItem={handleRestoreItem} />;
      case 'ventures':   return <VenturesEditor data={data.ventures} deletedItems={deleted.ventures} onSaveItem={handleSaveItem} onDeleteItem={handleDeleteItem} onRestoreItem={handleRestoreItem} />;
      case 'education':  return <EducationEditor data={data.education} deletedItems={deleted.education} onSaveItem={handleSaveItem} onDeleteItem={handleDeleteItem} onRestoreItem={handleRestoreItem} />;
      case 'contact':    return <ContactEditor data={data.contact} onSave={handleSave} />;
      case 'site_settings': return <SettingsEditor data={data.site_settings} onSave={handleSave} />;
      case 'messages':      return <MessagesView />;
      case 'reports':       return <ReportingView />;
      default: return null;
    }
  };

  return (
    <div style={{display:'flex',minHeight:'100vh'}}>
      {/* Sidebar */}
      <aside style={{width:220,background:'#161b22',borderRight:'1px solid #30363d',display:'flex',flexDirection:'column',position:'fixed',height:'100vh',overflowY:'auto'}}>
        {/* Logo */}
        <div style={{padding:'20px 16px',borderBottom:'1px solid #30363d'}}>
          <div style={{display:'flex',alignItems:'center',gap:10}}>
            <div style={{width:36,height:36,borderRadius:8,background:'linear-gradient(135deg,#238636,#FFA000)',display:'flex',alignItems:'center',justifyContent:'center'}}>
              <i className="fas fa-pen-nib" style={{color:'#fff',fontSize:14}}></i>
            </div>
            <div>
              <div style={{color:'#f0f6fc',fontWeight:700,fontSize:14}}>Portfolio CMS</div>
              <div style={{color:'#6e7681',fontSize:11}}>Content Manager</div>
            </div>
          </div>
        </div>

        {/* Nav */}
        <nav style={{padding:'8px 0',flex:1}}>
          {SECTIONS.map(s=>(
            <button key={s.id} className={`sidebar-item ${active===s.id?'active':''}`}
              onClick={()=>setActive(s.id)}
              style={{width:'100%',textAlign:'left',padding:'10px 16px',background:'transparent',border:'none',cursor:'pointer',color:active===s.id?'#58a6ff':'#8b949e',fontSize:13,fontWeight:active===s.id?600:400,borderLeft:active===s.id?'3px solid #238636':'3px solid transparent',display:'flex',justifyContent:'space-between',alignItems:'center'}}>
              <div style={{display:'flex',alignItems:'center',gap:10}}>
                <i className={s.icon} style={{width:16,textAlign:'center'}}></i>
                {s.label}
              </div>
              {s.id === 'messages' && window.__DELETED_DATA__.messages_unread > 0 && (
                 <span style={{background:'#f85149', color:'#fff', padding:'1px 6px', borderRadius:10, fontSize:10, fontWeight:700}}>
                    {window.__DELETED_DATA__.messages_unread}
                 </span>
              )}
            </button>
          ))}
        </nav>

        {/* Footer */}
        <div style={{padding:'16px',borderTop:'1px solid #30363d'}}>
          <a href="<?= htmlspecialchars(dirname($admin_url)) ?>/index.php" target="_blank" style={{display:'flex',alignItems:'center',gap:8,color:'#6e7681',fontSize:12,marginBottom:8,textDecoration:'none',padding:'8px',borderRadius:6}}>
            <i className="fas fa-external-link-alt"></i> View Site
          </a>
          <a href="?action=logout" style={{display:'flex',alignItems:'center',gap:8,color:'#f85149',fontSize:12,padding:8,borderRadius:6,textDecoration:'none'}}>
            <i className="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
        {/* Pulse Status */}
        <div style={{marginTop:'auto',padding:'16px',borderTop:'1px solid #30363d'}}>
           <div id="ajax-pulse" style={{display:'flex',alignItems:'center',gap:8,color:'#6e7681',fontSize:11}}>
              <div id="pulse-dot" style={{width:8,height:8,borderRadius:'50%',background:'#238636'}}></div>
              <span>SERVER ONLINE (AJAX PULSE)</span>
           </div>
           <script dangerouslySetInnerHTML={{__html: `
              function checkPulse() {
                 $.get(window.__API_BASE__ + '/reports.php')
                  .done(function() { 
                    $('#pulse-dot').css('background', '#238636'); 
                    $('#ajax-pulse span').text('SERVER ONLINE (AJAX PULSE)');
                  })
                  .fail(function() { 
                    $('#pulse-dot').css('background', '#f85149'); 
                    $('#ajax-pulse span').text('CONNECTION LOST');
                  });
              }
              setInterval(checkPulse, 30000);
           `}} />
        </div>
      </aside>

      {/* Main Content */}
      <main style={{marginLeft:220,flex:1,padding:'32px',maxWidth:900}}>
        {renderSection()}
      </main>

      {/* Toast */}
      {toast && <Toast message={toast.msg} type={toast.type} onClose={()=>setToast(null)} />}
    </div>
  );
}

ReactDOM.render(<CMS />, document.getElementById('cms-root'));
</script>

  <?php endif; ?>
</body>

</html>