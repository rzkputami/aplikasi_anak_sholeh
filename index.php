<?php
session_start();

define('DATA_DIR', __DIR__ . '/data/');
if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

function loadData($file) {
    $path = DATA_DIR . $file . '.json';
    if (!file_exists($path)) return [];
    return json_decode(file_get_contents($path), true) ?? [];
}

function saveData($file, $data) {
    file_put_contents(DATA_DIR . $file . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function initDefaults() {
    if (!file_exists(DATA_DIR . 'settings.json')) {
        saveData('settings', [
            'sholat'     => ['Subuh', 'Dzuhur', 'Ashar', 'Maghrib', 'Isya'],
            'surah_juz30'=> [
                'An-Naba',"An-Nazi'at",'Abasa','At-Takwir','Al-Infitar',
                'Al-Mutaffifin','Al-Insyiqaq','Al-Buruj','At-Tariq',"Al-A'la",
                'Al-Ghasyiyah','Al-Fajr','Al-Balad','Asy-Syams','Al-Lail',
                'Ad-Duha','Asy-Syarh','At-Tin','Al-Alaq','Al-Qadr',
                'Al-Bayyinah','Az-Zalzalah','Al-Adiyat',"Al-Qari'ah",'At-Takatsur',
                'Al-Asr','Al-Humazah','Al-Fil','Quraisy',"Al-Ma'un",
                'Al-Kautsar','Al-Kafirun','An-Nasr','Al-Masad','Al-Ikhlas',
                'Al-Falaq','An-Nas'
            ],
            'doa'  => [
                "Do'a Sebelum Makan","Do'a Sesudah Makan","Do'a Sebelum Tidur",
                "Do'a Bangun Tidur","Do'a Masuk Kamar Mandi","Do'a Keluar Kamar Mandi",
                "Do'a Masuk Rumah","Do'a Keluar Rumah","Do'a Naik Kendaraan",
                "Do'a Untuk Orang Tua","Do'a Belajar"
            ],
            'hadist' => [
                'Hadist Kebersihan','Hadist Menuntut Ilmu','Hadist Berbakti kepada Orang Tua',
                'Hadist Senyum adalah Sedekah','Hadist Kasih Sayang','Hadist Kejujuran'
            ],
            'amalan' => [
                'Sedekah','Membantu Orang Tua','Berkata Jujur','Berbagi dengan Teman',
                "Membaca Al-Qur'an",'Berdzikir','Menjaga Kebersihan','Berbuat Baik'
            ],
            'hadiah' => [
                ['bintang'=>10,'hadiah'=>'Stiker Bintang'],
                ['bintang'=>25,'hadiah'=>'Buku Mewarnai'],
                ['bintang'=>50,'hadiah'=>'Mainan Kecil'],
                ['bintang'=>100,'hadiah'=>'Hadiah Spesial'],
            ]
        ]);
    }
    if (!file_exists(DATA_DIR . 'children.json'))  saveData('children', []);
    if (!file_exists(DATA_DIR . 'activities.json')) saveData('activities', []);
}
initDefaults();

/* ─── POST handlers ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_child') {
        $children   = loadData('children');
        $children[] = ['id'=>uniqid(),'nama'=>htmlspecialchars($_POST['nama']),'usia'=>intval($_POST['usia']),'created_at'=>date('Y-m-d')];
        saveData('children', $children);
        header('Location: index.php?page=children&success=1'); exit;
    }

    if ($action === 'delete_child') {
        $children = loadData('children');
        $children = array_values(array_filter($children, fn($c) => $c['id'] !== $_POST['child_id']));
        saveData('children', $children);
        header('Location: index.php?page=children'); exit;
    }

    if ($action === 'save_activity') {
        $activities = loadData('activities');
        $date=$_POST['date']??date('Y-m-d'); $child_id=$_POST['child_id']; $type=$_POST['type']; $item=$_POST['item']; $stars=intval($_POST['stars']??1);
        $activities = array_filter($activities, fn($a)=>!($a['date']===$date&&$a['child_id']===$child_id&&$a['type']===$type&&$a['item']===$item));
        $activities[] = ['id'=>uniqid(),'child_id'=>$child_id,'date'=>$date,'type'=>$type,'item'=>$item,'stars'=>$stars,'done'=>true];
        saveData('activities', array_values($activities));
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'remove_activity') {
        $activities = loadData('activities');
        $date=$_POST['date']??date('Y-m-d'); $child_id=$_POST['child_id']; $type=$_POST['type']; $item=$_POST['item'];
        $activities = array_values(array_filter($activities, fn($a)=>!($a['date']===$date&&$a['child_id']===$child_id&&$a['type']===$type&&$a['item']===$item)));
        saveData('activities', $activities);
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'save_iqro') {
        $iqroData = loadData('iqro_records');
        $key = $_POST['child_id'].'_'.$_POST['date'];
        $iqroData[$key] = ['child_id'=>$_POST['child_id'],'date'=>$_POST['date'],'jilid'=>htmlspecialchars($_POST['jilid']??''),'halaman'=>htmlspecialchars($_POST['halaman']??''),'catatan'=>htmlspecialchars($_POST['catatan']??''),'stars'=>intval($_POST['stars']??0),'updated_at'=>date('Y-m-d H:i:s')];
        saveData('iqro_records', $iqroData);
        $activities = loadData('activities');
        $activities = array_filter($activities, fn($a)=>!($a['child_id']===$_POST['child_id']&&$a['date']===$_POST['date']&&$a['type']==='iqro'));
        if (intval($_POST['stars']??0)>0) {
            $activities[] = ['id'=>uniqid(),'child_id'=>$_POST['child_id'],'date'=>$_POST['date'],'type'=>'iqro','item'=>'Iqro '.$_POST['jilid'].' Hal.'.$_POST['halaman'],'stars'=>intval($_POST['stars']),'done'=>true];
        }
        saveData('activities', array_values($activities));
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'save_doa') {
        $data = loadData('doa_records');
        $key  = $_POST['child_id'].'_'.$_POST['date'];
        $data[$key] = [
            'child_id'  => $_POST['child_id'],
            'date'      => $_POST['date'],
            'doa'       => htmlspecialchars($_POST['doa']  ?? ''),
            'catatan'   => htmlspecialchars($_POST['catatan'] ?? ''),
            'stars'     => intval($_POST['stars'] ?? 0),
            'updated_at'=> date('Y-m-d H:i:s')
        ];
        saveData('doa_records', $data);
        // sync ke activities
        $activities = loadData('activities');
        $activities = array_filter($activities, fn($a)=>!($a['child_id']===$_POST['child_id']&&$a['date']===$_POST['date']&&$a['type']==='doa_form'));
        if (intval($_POST['stars']??0)>0) {
            $activities[] = ['id'=>uniqid(),'child_id'=>$_POST['child_id'],'date'=>$_POST['date'],'type'=>'doa_form','item'=>'Do\'a: '.htmlspecialchars($_POST['doa']??'-'),'stars'=>intval($_POST['stars']),'done'=>true];
        }
        saveData('activities', array_values($activities));
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'save_hadist') {
        $data = loadData('hadist_records');
        $key  = $_POST['child_id'].'_'.$_POST['date'];
        $data[$key] = [
            'child_id'  => $_POST['child_id'],
            'date'      => $_POST['date'],
            'hadist'    => htmlspecialchars($_POST['hadist'] ?? ''),
            'catatan'   => htmlspecialchars($_POST['catatan'] ?? ''),
            'stars'     => intval($_POST['stars'] ?? 0),
            'updated_at'=> date('Y-m-d H:i:s')
        ];
        saveData('hadist_records', $data);
        // sync ke activities
        $activities = loadData('activities');
        $activities = array_filter($activities, fn($a)=>!($a['child_id']===$_POST['child_id']&&$a['date']===$_POST['date']&&$a['type']==='hadist_form'));
        if (intval($_POST['stars']??0)>0) {
            $activities[] = ['id'=>uniqid(),'child_id'=>$_POST['child_id'],'date'=>$_POST['date'],'type'=>'hadist_form','item'=>'Hadist: '.htmlspecialchars($_POST['hadist']??'-'),'stars'=>intval($_POST['stars']),'done'=>true];
        }
        saveData('activities', array_values($activities));
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'save_settings') {
        $settings = loadData('settings');
        $settings[$_POST['field']] = json_decode($_POST['value'], true);
        saveData('settings', $settings);
        echo json_encode(['success'=>true]); exit;
    }

    if ($action === 'save_weekly_note') {
        $notes = loadData('weekly_notes');
        $key   = $_POST['child_id'].'_'.$_POST['week'];
        $notes[$key] = ['child_id'=>$_POST['child_id'],'week'=>$_POST['week'],'diniyah'=>htmlspecialchars($_POST['diniyah']??''),'bilangan_literasi'=>htmlspecialchars($_POST['bilangan_literasi']??''),'tematik'=>htmlspecialchars($_POST['tematik']??''),'updated_at'=>date('Y-m-d H:i:s')];
        saveData('weekly_notes', $notes);
        echo json_encode(['success'=>true]); exit;
    }
}

/* ─── Helpers ─── */
$page       = $_GET['page'] ?? 'dashboard';
$settings   = loadData('settings');
$children   = loadData('children');
$activities = loadData('activities');

function getTotalStars($child_id, $activities) {
    return array_sum(array_column(array_filter($activities, fn($a)=>$a['child_id']===$child_id), 'stars'));
}
function getWeekDates() {
    $mon=[]; $m=date('Y-m-d',strtotime('monday this week'));
    for($i=0;$i<7;$i++) $mon[]=date('Y-m-d',strtotime($m." +{$i} days"));
    return $mon;
}
function getWeekLabel() {
    return 'Minggu '.date('d M',strtotime('monday this week')).' - '.date('d M Y',strtotime('sunday this week'));
}

$navItems = [
    'dashboard' => ['icon'=>'🏠','label'=>'Beranda'],
    'children'  => ['icon'=>'👶','label'=>'Anak'],
    'daily'     => ['icon'=>'📅','label'=>'Harian'],
    'weekly'    => ['icon'=>'📝','label'=>'Pekanan'],
    'stars'     => ['icon'=>'⭐','label'=>'Bintang'],
    'settings'  => ['icon'=>'⚙️','label'=>'Atur'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#6C3FC5">
<meta name="apple-mobile-web-app-capable" content="yes">
<title>Anak Soleh</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Baloo+2:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ── SVG Background Decorations ── -->
<div class="bg-deco" aria-hidden="true">
  <!-- ROCKET -->
  <svg class="deco-rocket" viewBox="0 0 80 120" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="40" cy="55" rx="18" ry="32" fill="#A78BFA"/>
    <polygon points="40,8 25,40 55,40" fill="#7C3AED"/>
    <ellipse cx="40" cy="40" rx="8" ry="8" fill="#E0D7FF"/>
    <rect x="18" y="52" width="10" height="18" rx="5" fill="#C4B5FD" transform="rotate(-20,23,61)"/>
    <rect x="52" y="52" width="10" height="18" rx="5" fill="#C4B5FD" transform="rotate(20,57,61)"/>
    <ellipse cx="33" cy="90" rx="5" ry="10" fill="#FCD34D" opacity=".85"/>
    <ellipse cx="40" cy="93" rx="6" ry="13" fill="#F97316" opacity=".8"/>
    <ellipse cx="47" cy="90" rx="5" ry="10" fill="#FCD34D" opacity=".85"/>
  </svg>
  <!-- BEAR -->
  <svg class="deco-bear" viewBox="0 0 90 100" xmlns="http://www.w3.org/2000/svg">
    <circle cx="20" cy="22" r="14" fill="#C4B5FD"/>
    <circle cx="70" cy="22" r="14" fill="#C4B5FD"/>
    <circle cx="45" cy="55" r="32" fill="#DDD6FE"/>
    <circle cx="45" cy="44" r="22" fill="#EDE9FE"/>
    <circle cx="35" cy="40" r="5" fill="#7C3AED"/>
    <circle cx="55" cy="40" r="5" fill="#7C3AED"/>
    <circle cx="36" cy="38" r="2" fill="white"/>
    <circle cx="56" cy="38" r="2" fill="white"/>
    <ellipse cx="45" cy="52" rx="10" ry="7" fill="#C4B5FD"/>
    <ellipse cx="45" cy="53" rx="5" ry="3" fill="#7C3AED"/>
    <path d="M38,58 Q45,64 52,58" stroke="#7C3AED" stroke-width="2" fill="none" stroke-linecap="round"/>
    <circle cx="20" cy="24" r="8" fill="#EDE9FE"/>
    <circle cx="70" cy="24" r="8" fill="#EDE9FE"/>
  </svg>
  <!-- small stars / moons -->
  <span class="deco-star ds1">✨</span>
  <span class="deco-star ds2">🌙</span>
  <span class="deco-star ds3">⭐</span>
  <span class="deco-star ds4">✨</span>
  <!-- bubbles -->
  <div class="bubble bbl1"></div>
  <div class="bubble bbl2"></div>
  <div class="bubble bbl3"></div>
</div>

<div class="app-layout">

  <!-- ════ SIDEBAR (desktop) ════ -->
  <aside class="sidebar">
    <div class="logo">
      <svg class="logo-rocket" viewBox="0 0 40 60" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="20" cy="27" rx="9" ry="16" fill="#C4B5FD"/>
        <polygon points="20,4 12,20 28,20" fill="white"/>
        <ellipse cx="20" cy="20" rx="4" ry="4" fill="#7C3AED"/>
        <ellipse cx="16" cy="44" rx="3" ry="6" fill="#FCD34D" opacity=".9"/>
        <ellipse cx="20" cy="47" rx="3.5" ry="7" fill="#F97316" opacity=".85"/>
        <ellipse cx="24" cy="44" rx="3" ry="6" fill="#FCD34D" opacity=".9"/>
      </svg>
      <span class="logo-text">Anak Soleh</span>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($navItems as $pg => $n): ?>
      <a href="?page=<?= $pg ?>" class="snav-item <?= $page===$pg?'active':'' ?>">
        <span class="snav-icon"><?= $n['icon'] ?></span>
        <span><?= $n['label'] ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <p>"Didiklah anakmu sesuai zamannya"</p>
    </div>
  </aside>

  <!-- ════ MAIN ════ -->
  <main class="main-content">

    <!-- Mobile top bar -->
    <header class="mobile-topbar">
      <div class="mtb-logo">
        <svg viewBox="0 0 24 36" width="22" xmlns="http://www.w3.org/2000/svg">
          <ellipse cx="12" cy="16" rx="6" ry="10" fill="#C4B5FD"/>
          <polygon points="12,2 7,12 17,12" fill="white"/>
          <ellipse cx="10" cy="27" rx="2" ry="4" fill="#FCD34D"/>
          <ellipse cx="12" cy="29" rx="2.2" ry="4.5" fill="#F97316"/>
          <ellipse cx="14" cy="27" rx="2" ry="4" fill="#FCD34D"/>
        </svg>
        <span>Anak Soleh</span>
      </div>
      <div class="mtb-title"><?= $navItems[$page]['icon'].' '.$navItems[$page]['label'] ?></div>
    </header>

    <!-- Page content -->
    <div class="page-wrap">

    <?php
    /* ══════════════════════════════════════════
       PAGE: DASHBOARD
    ══════════════════════════════════════════ */
    if ($page === 'dashboard'):
    ?>
    <div class="page-header">
      <h1>Assalamu'alaikum 👋</h1>
      <p class="subtitle"><?= getWeekLabel() ?></p>
    </div>

    <?php if (empty($children)): ?>
    <div class="empty-state">
      <div class="empty-icon">👶</div>
      <h2>Belum ada data anak</h2>
      <p>Tambahkan data anak terlebih dahulu untuk mulai tracking</p>
      <a href="?page=children" class="btn btn-primary">+ Tambah Anak</a>
    </div>
    <?php else: ?>
    <div class="child-cards">
      <?php foreach ($children as $child):
        $ts = getTotalStars($child['id'], $activities);
        $wa = array_filter($activities, fn($a)=>$a['child_id']===$child['id']&&in_array($a['date'],getWeekDates()));
        $ws = array_sum(array_column($wa,'stars'));
        $nr = null; foreach($settings['hadiah'] as $h){if($h['bintang']>$ts){$nr=$h;break;}}
        $prog = $nr ? min(100,($ts/$nr['bintang'])*100) : 100;
      ?>
      <div class="child-card">
        <div class="card-top-bar"></div>
        <div class="child-card-inner">
          <div class="child-avatar"><?= strtoupper(substr($child['nama'],0,1)) ?></div>
          <div class="child-info">
            <h2><?= htmlspecialchars($child['nama']) ?></h2>
            <p><?= $child['usia'] ?> tahun</p>
          </div>
        </div>
        <div class="child-stats">
          <div class="stat-box">
            <div class="stat-num"><?= $ts ?></div>
            <div class="stat-lbl">Total ⭐</div>
          </div>
          <div class="stat-box">
            <div class="stat-num"><?= $ws ?></div>
            <div class="stat-lbl">Minggu ini</div>
          </div>
        </div>
        <?php if ($nr): ?>
        <div class="reward-row">
          <span>🎯 <?= htmlspecialchars($nr['hadiah']) ?></span>
          <span><?= $ts ?>/<?= $nr['bintang'] ?> ⭐</span>
        </div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?= $prog ?>%"></div></div>
        <?php endif; ?>
        <div class="child-btns">
          <a href="?page=daily&child=<?= $child['id'] ?>" class="btn btn-primary btn-sm">📅 Catat Kegiatan</a>
          <a href="?page=stars&child=<?= $child['id'] ?>"  class="btn btn-ghost  btn-sm">⭐ Bintang</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    /* ══════════════════════════════════════════
       PAGE: CHILDREN
    ══════════════════════════════════════════ */
    elseif ($page === 'children'):
    ?>
    <div class="page-header">
      <h1>👶 Data Anak</h1>
    </div>
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Data anak berhasil ditambahkan!</div>
    <?php endif; ?>
    <div class="two-col">
      <div class="card">
        <div class="card-header"><h2>➕ Tambah Anak Baru</h2></div>
        <form method="POST" class="form">
          <input type="hidden" name="action" value="add_child">
          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" placeholder="Masukkan nama anak..." required>
          </div>
          <div class="form-group">
            <label>Usia (tahun)</label>
            <input type="number" name="usia" placeholder="Contoh: 5" min="1" max="18" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">💾 Simpan</button>
        </form>
      </div>
      <div class="card">
        <div class="card-header"><h2>📋 Daftar Anak</h2></div>
        <?php if (empty($children)): ?>
        <p class="text-muted tc">Belum ada data anak</p>
        <?php else: ?>
        <div class="child-list">
          <?php foreach ($children as $child): $st=getTotalStars($child['id'],$activities); ?>
          <div class="child-list-item">
            <div class="cl-av"><?= strtoupper(substr($child['nama'],0,1)) ?></div>
            <div class="cl-info">
              <strong><?= htmlspecialchars($child['nama']) ?></strong>
              <span><?= $child['usia'] ?> tahun · <?= $st ?> ⭐</span>
            </div>
            <form method="POST" onsubmit="return confirm('Yakin hapus?')">
              <input type="hidden" name="action" value="delete_child">
              <input type="hidden" name="child_id" value="<?= $child['id'] ?>">
              <button type="submit" class="btn-icon btn-danger">🗑️</button>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php
    /* ══════════════════════════════════════════
       PAGE: DAILY
    ══════════════════════════════════════════ */
    elseif ($page === 'daily'):
    ?>
    <div class="page-header">
      <h1>📅 Kegiatan Harian</h1>
    </div>
    <?php if (empty($children)): ?>
    <div class="empty-state"><p>Tambah data anak dulu</p><a href="?page=children" class="btn btn-primary">+ Tambah Anak</a></div>
    <?php else:
      $selChild    = $_GET['child'] ?? ($children[0]['id']??'');
      $selDate     = $_GET['date']  ?? date('Y-m-d');
      $selCD_arr   = array_filter($children, fn($c)=>$c['id']===$selChild);
      $selCD       = reset($selCD_arr);
      $dayActs     = array_filter($activities, fn($a)=>$a['child_id']===$selChild&&$a['date']===$selDate);
      $doneItems   = [];
      foreach ($dayActs as $a) $doneItems[$a['type'].'_'.$a['item']] = $a['stars'];
      $cats = [
        'amalan' =>['icon'=>'🤲','label'=>'Amalan Baik',        'color'=>'cat-green',  'items'=>$settings['amalan']??[]],
        'sholat' =>['icon'=>'🕌','label'=>'Sholat 5 Waktu',     'color'=>'cat-blue',   'items'=>$settings['sholat']],
        'surah'  =>['icon'=>'📖','label'=>'Hafalan Surah Juz 30','color'=>'cat-purple', 'items'=>$settings['surah_juz30']],
      ];
      // Doa record
      $doaRecs  = loadData('doa_records');
      $doaKey   = $selChild.'_'.$selDate;
      $doaRec   = $doaRecs[$doaKey] ?? ['doa'=>'','catatan'=>'','stars'=>0];
      // Hadist record
      $hadistRecs = loadData('hadist_records');
      $hadistKey  = $selChild.'_'.$selDate;
      $hadistRec  = $hadistRecs[$hadistKey] ?? ['hadist'=>'','catatan'=>'','stars'=>0];
      // Iqro record
      $iqroRecs  = loadData('iqro_records');
      $iqroKey   = $selChild.'_'.$selDate;
      $iqroRec   = $iqroRecs[$iqroKey] ?? ['jilid'=>'','halaman'=>'','catatan'=>'','stars'=>0];
      $jilidOpts = ['Iqro 1','Iqro 2','Iqro 3','Iqro 4','Iqro 5','Iqro 6',"Al-Qur'an"];
    ?>
    <!-- selector bar -->
    <div class="sel-bar">
      <div class="form-group">
        <label>Anak</label>
        <select id="childSel" onchange="goDaily()">
          <?php foreach ($children as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $selChild===$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Tanggal</label>
        <input type="date" id="dateSel" value="<?= $selDate ?>" onchange="goDaily()">
      </div>
    </div>

    <div class="child-banner">
      <div class="cb-av"><?= strtoupper(substr($selCD['nama'],0,1)) ?></div>
      <div class="cb-info">
        <strong><?= htmlspecialchars($selCD['nama']) ?></strong>
        <span><?= date('d M Y', strtotime($selDate)) ?></span>
      </div>
      <div class="cb-stars">⭐ <?= getTotalStars($selChild,$activities) ?></div>
    </div>

    <div class="act-grid">
    <?php foreach ($cats as $type => $cat): ?>
    <div class="act-card <?= $cat['color'] ?>">
      <div class="ac-hdr">
        <span><?= $cat['icon'] ?></span>
        <h3><?= $cat['label'] ?></h3>
        <span class="ac-cnt"><?= count(array_filter(array_keys($doneItems),fn($k)=>str_starts_with($k,$type.'_'))) ?>/<?= count($cat['items']) ?></span>
      </div>
      <div class="ac-list">
        <?php foreach ($cat['items'] as $item):
          $k=$type.'_'.$item; $done=isset($doneItems[$k]); $st=$doneItems[$k]??1;
        ?>
        <div class="ac-item <?= $done?'done':'' ?>"
             data-type="<?= $type ?>" data-item="<?= htmlspecialchars($item,ENT_QUOTES) ?>"
             data-child="<?= $selChild ?>" data-date="<?= $selDate ?>" data-done="<?= $done?'1':'0' ?>">
          <div class="ac-chk" onclick="toggleAct(this.closest('.ac-item'))">
            <span class="chk-ico"><?= $done?'✅':'⬜' ?></span>
          </div>
          <span class="ac-name"><?= htmlspecialchars($item) ?></span>
          <div class="star-row">
            <?php for($s=1;$s<=3;$s++): ?>
            <button class="s-btn <?= ($done&&$st>=$s)?'lit':'' ?>" onclick="setStars(this,<?= $s ?>)">⭐</button>
            <?php endfor; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div><!-- /.act-grid -->

        <!-- ══ ROW: DOA · HADIST · IQRO ══ -->
    <div class="form-cards-row">

      <!-- ── DOA CARD ── -->
      <div class="form-card fc-orange">
        <div class="fc-hdr">
          <span>🤲</span>
          <h3>Hafalan Do'a</h3>
          <?php if($doaRec['stars']>0): ?>
          <span class="fc-badge">✅ <?= str_repeat('⭐',$doaRec['stars']) ?></span>
          <?php endif; ?>
        </div>
        <div class="fc-body">
          <div class="form-group">
            <label>🤲 Do'a</label>
            <select id="doaPilih">
              <option value="">-- Pilih Do'a --</option>
              <?php foreach($settings['doa'] as $d): ?>
              <option value="<?= htmlspecialchars($d) ?>" <?= $doaRec['doa']===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>📝 Catatan</label>
            <textarea id="doaCat" placeholder="Catatan bacaan do'a..."><?= htmlspecialchars($doaRec['catatan']) ?></textarea>
          </div>
          <div class="form-group">
            <label>⭐ Penilaian</label>
            <div class="fc-stars" id="doaStars">
              <?php for($s=1;$s<=4;$s++): ?>
              <button class="fc-star-btn <?= $doaRec['stars']>=$s?'lit':'' ?>" onclick="pickStar('doa',<?= $s ?>)" type="button">⭐</button>
              <?php endfor; ?>
              <span class="fc-star-lbl" id="doaStarLbl"><?= $doaRec['stars']>0?$doaRec['stars'].' Bintang':'Pilih penilaian' ?></span>
            </div>
            <div class="fc-star-desc" id="doaDesc"><?php
              $ds4=['','😕 Perlu latihan','🙂 Cukup','😊 Bagus!','🌟 Sempurna!'];
              echo $doaRec['stars']>0?$ds4[$doaRec['stars']]:'';
            ?></div>
          </div>
        </div>
        <div class="fc-footer">
          <button class="btn btn-primary btn-full" onclick="saveDoa('<?= $selChild ?>','<?= $selDate ?>')" type="button">💾 Simpan Do'a</button>
          <?php if($doaRec['stars']>0): ?>
          <button class="btn btn-ghost btn-full mt8" onclick="clearDoa('<?= $selChild ?>','<?= $selDate ?>')" type="button">🗑️ Hapus</button>
          <?php endif; ?>
          <div id="doaMsg"></div>
        </div>
      </div>

      <!-- ── HADIST CARD ── -->
      <div class="form-card fc-pink">
        <div class="fc-hdr">
          <span>📜</span>
          <h3>Hafalan Hadist</h3>
          <?php if($hadistRec['stars']>0): ?>
          <span class="fc-badge">✅ <?= str_repeat('⭐',$hadistRec['stars']) ?></span>
          <?php endif; ?>
        </div>
        <div class="fc-body">
          <div class="form-group">
            <label>📜 Hadist</label>
            <select id="hadistPilih">
              <option value="">-- Pilih Hadist --</option>
              <?php foreach($settings['hadist'] as $h): ?>
              <option value="<?= htmlspecialchars($h) ?>" <?= $hadistRec['hadist']===$h?'selected':'' ?>><?= htmlspecialchars($h) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>📝 Catatan</label>
            <textarea id="hadistCat" placeholder="Catatan hafalan hadist..."><?= htmlspecialchars($hadistRec['catatan']) ?></textarea>
          </div>
          <div class="form-group">
            <label>⭐ Penilaian</label>
            <div class="fc-stars" id="hadistStars">
              <?php for($s=1;$s<=4;$s++): ?>
              <button class="fc-star-btn <?= $hadistRec['stars']>=$s?'lit':'' ?>" onclick="pickStar('hadist',<?= $s ?>)" type="button">⭐</button>
              <?php endfor; ?>
              <span class="fc-star-lbl" id="hadistStarLbl"><?= $hadistRec['stars']>0?$hadistRec['stars'].' Bintang':'Pilih penilaian' ?></span>
            </div>
            <div class="fc-star-desc" id="hadistDesc"><?php
              echo $hadistRec['stars']>0?$ds4[$hadistRec['stars']]:'';
            ?></div>
          </div>
        </div>
        <div class="fc-footer">
          <button class="btn btn-primary btn-full" onclick="saveHadist('<?= $selChild ?>','<?= $selDate ?>')" type="button">💾 Simpan Hadist</button>
          <?php if($hadistRec['stars']>0): ?>
          <button class="btn btn-ghost btn-full mt8" onclick="clearHadist('<?= $selChild ?>','<?= $selDate ?>')" type="button">🗑️ Hapus</button>
          <?php endif; ?>
          <div id="hadistMsg"></div>
        </div>
      </div>

      <!-- ── IQRO CARD ── -->
      <div class="form-card fc-teal">
        <div class="fc-hdr">
          <span>📚</span>
          <h3>Bacaan Iqro</h3>
          <?php if($iqroRec['stars']>0): ?>
          <span class="fc-badge">✅ <?= str_repeat('⭐',$iqroRec['stars']) ?></span>
          <?php endif; ?>
        </div>
        <div class="fc-body">
          <div class="form-group">
            <label>📖 Jilid</label>
            <select id="iqroJilid">
              <option value="">-- Pilih Jilid --</option>
              <?php foreach($jilidOpts as $j): ?>
              <option value="<?= $j ?>" <?= $iqroRec['jilid']===$j?'selected':'' ?>><?= $j ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>📄 Halaman</label>
            <input type="number" id="iqroHal" min="1" max="200" placeholder="Contoh: 15" value="<?= htmlspecialchars($iqroRec['halaman']) ?>">
          </div>
          <div class="form-group">
            <label>📝 Catatan</label>
            <textarea id="iqroCat" placeholder="Catatan guru / orang tua..."><?= htmlspecialchars($iqroRec['catatan']) ?></textarea>
          </div>
          <div class="form-group">
            <label>⭐ Penilaian</label>
            <div class="fc-stars" id="iqroStars">
              <?php for($s=1;$s<=4;$s++): ?>
              <button class="fc-star-btn <?= $iqroRec['stars']>=$s?'lit':'' ?>" onclick="pickStar('iqro',<?= $s ?>)" type="button">⭐</button>
              <?php endfor; ?>
              <span class="fc-star-lbl" id="iqroStarLbl"><?= $iqroRec['stars']>0?$iqroRec['stars'].' Bintang':'Pilih penilaian' ?></span>
            </div>
            <div class="fc-star-desc" id="iqroDesc"><?php
              echo $iqroRec['stars']>0?$ds4[$iqroRec['stars']]:'';
            ?></div>
          </div>
        </div>
        <div class="fc-footer">
          <button class="btn btn-primary btn-full" onclick="saveIqro('<?= $selChild ?>','<?= $selDate ?>')" type="button">💾 Simpan Iqro</button>
          <?php if($iqroRec['stars']>0): ?>
          <button class="btn btn-ghost btn-full mt8" onclick="clearIqro('<?= $selChild ?>','<?= $selDate ?>')" type="button">🗑️ Hapus</button>
          <?php endif; ?>
          <div id="iqroMsg"></div>
        </div>
      </div>

    </div><!-- /.form-cards-row -->

    <script>
    function goDaily(){
      window.location.href='?page=daily&child='+document.getElementById('childSel').value+'&date='+document.getElementById('dateSel').value;
    }
    function setStars(btn,stars){ saveAct(btn.closest('.ac-item'),stars); }
    function toggleAct(item){
      if(item.dataset.done==='1') removeAct(item); else saveAct(item,1);
    }
    function saveAct(item,stars){
      fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({action:'save_activity',child_id:item.dataset.child,date:item.dataset.date,type:item.dataset.type,item:item.dataset.item,stars})
      }).then(()=>{
        item.dataset.done='1'; item.classList.add('done');
        item.querySelector('.chk-ico').textContent='✅';
        item.querySelectorAll('.s-btn').forEach((b,i)=>b.classList.toggle('lit',i<stars));
        toast('Tercatat! '+'⭐'.repeat(Math.min(stars,3)));
      });
    }
    function removeAct(item){
      fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({action:'remove_activity',child_id:item.dataset.child,date:item.dataset.date,type:item.dataset.type,item:item.dataset.item})
      }).then(()=>{
        item.dataset.done='0'; item.classList.remove('done');
        item.querySelector('.chk-ico').textContent='⬜';
        item.querySelectorAll('.s-btn').forEach(b=>b.classList.remove('lit'));
      });
    }

    // ── STAR RATING SYSTEM (Doa, Hadist, Iqro) ──
    const starState = {
      doa: <?= $doaRec['stars'] ?? 0 ?>,
      hadist: <?= $hadistRec['stars'] ?? 0 ?>,
      iqro: <?= $iqroRec['stars'] ?? 0 ?>
    };
    const starDesc = ['','😕 Perlu latihan','🙂 Cukup','😊 Bagus!','🌟 Sempurna!'];

    function pickStar(type, value) {
      starState[type] = value;
      const container = document.getElementById(type + 'Stars');
      container.querySelectorAll('.fc-star-btn').forEach((btn, i) => {
        btn.classList.toggle('lit', i < value);
      });
      document.getElementById(type + 'StarLbl').textContent = value + ' Bintang';
      document.getElementById(type + 'Desc').textContent = starDesc[value];
    }

    // ── SAVE DOA ──
    function saveDoa(cid, dt) {
      const doa = document.getElementById('doaPilih').value;
      const cat = document.getElementById('doaCat').value;
      const stars = starState.doa;
      if (!doa) { alert('Pilih doa terlebih dahulu!'); return; }
      if (!stars) { alert('Pilih penilaian bintang!'); return; }
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action: 'save_doa', child_id: cid, date: dt,
          doa: doa, catatan: cat, stars: stars
        })
      }).then(() => {
        document.getElementById('doaMsg').innerHTML = '<div class="alert alert-success">✅ Do'a tersimpan!</div>';
        setTimeout(() => document.getElementById('doaMsg').innerHTML = '', 3000);
        toast('Catatan Do'a tersimpan! ' + '⭐'.repeat(stars));
      });
    }
    function clearDoa(cid, dt) {
      if (!confirm('Hapus catatan Do'a?')) return;
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action: 'save_doa', child_id: cid, date: dt,
          doa: '', catatan: '', stars: 0
        })
      }).then(() => location.reload());
    }

    // ── SAVE HADIST ──
    function saveHadist(cid, dt) {
      const hadist = document.getElementById('hadistPilih').value;
      const cat = document.getElementById('hadistCat').value;
      const stars = starState.hadist;
      if (!hadist) { alert('Pilih hadist terlebih dahulu!'); return; }
      if (!stars) { alert('Pilih penilaian bintang!'); return; }
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action: 'save_hadist', child_id: cid, date: dt,
          hadist: hadist, catatan: cat, stars: stars
        })
      }).then(() => {
        document.getElementById('hadistMsg').innerHTML = '<div class="alert alert-success">✅ Hadist tersimpan!</div>';
        setTimeout(() => document.getElementById('hadistMsg').innerHTML = '', 3000);
        toast('Catatan Hadist tersimpan! ' + '⭐'.repeat(stars));
      });
    }
    function clearHadist(cid, dt) {
      if (!confirm('Hapus catatan Hadist?')) return;
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action: 'save_hadist', child_id: cid, date: dt,
          hadist: '', catatan: '', stars: 0
        })
      }).then(() => location.reload());
    }

    // ── SAVE IQRO ──
    function saveIqro(cid, dt) {
      const j = document.getElementById('iqroJilid').value;
      const h = document.getElementById('iqroHal').value;
      const c = document.getElementById('iqroCat').value;
      const stars = starState.iqro;
      if (!j) { alert('Pilih jilid!'); return; }
      if (!h) { alert('Isi halaman!'); return; }
      if (!stars) { alert('Pilih bintang!'); return; }
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action: 'save_iqro', child_id: cid, date: dt,
          jilid: j, halaman: h, catatan: c, stars: stars
        })
      }).then(() => {
        document.getElementById('iqroMsg').innerHTML = '<div class="alert alert-success">✅ Iqro tersimpan!</div>';
        setTimeout(() => document.getElementById('iqroMsg').innerHTML = '', 3000);
        toast('Catatan Iqro tersimpan! ' + '⭐'.repeat(stars));
      });
    }
    function clearIqro(cid, dt) {
      if (!confirm('Hapus catatan Iqro?')) return;
      fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          action: 'save_iqro', child_id: cid, date: dt,
          jilid: '', halaman: '', catatan: '', stars: 0
        })
      }).then(() => location.reload());
    }

    function toast(msg) {
      const t = document.createElement('div');
      t.className = 'toast';
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => t.classList.add('show'), 10);
      setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 2200);
    }
    </script>
    <?php endif; ?>

    <?php
    /* ══════════════════════════════════════════
       PAGE: WEEKLY
    ══════════════════════════════════════════ */
    elseif ($page === 'weekly'):
    ?>
    <div class="page-header">
      <h1>📝 Catatan Pekanan</h1>
      <p class="subtitle"><?= getWeekLabel() ?></p>
    </div>
    <?php if (empty($children)): ?>
    <div class="empty-state"><p>Tambah data anak dulu</p></div>
    <?php else:
      $selChild = $_GET['child'] ?? ($children[0]['id']??'');
      $week     = date('Y-W');
      $notes    = loadData('weekly_notes');
      $nk       = $selChild.'_'.$week;
      $note     = $notes[$nk] ?? ['diniyah'=>'','bilangan_literasi'=>'','tematik'=>''];
    ?>
    <div class="sel-bar">
      <div class="form-group">
        <label>Pilih Anak</label>
        <select onchange="location.href='?page=weekly&child='+this.value">
          <?php foreach($children as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $selChild===$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><h2>📚 Catatan Pembelajaran Pekanan</h2></div>
      <div class="sentra-stack">
        <div class="sentra sentra-d">
          <div class="sentra-hdr"><span>🕌</span><h3>Sentra Diniyah</h3></div>
          <textarea id="diniyah" placeholder="Catat pembelajaran diniyah minggu ini..."><?= htmlspecialchars($note['diniyah']) ?></textarea>
        </div>
        <div class="sentra sentra-b">
          <div class="sentra-hdr"><span>🔢</span><h3>Sentra Bilangan &amp; Literasi</h3></div>
          <textarea id="bilangan_literasi" placeholder="Catat pembelajaran bilangan dan literasi..."><?= htmlspecialchars($note['bilangan_literasi']) ?></textarea>
        </div>
        <div class="sentra sentra-t">
          <div class="sentra-hdr"><span>🎨</span><h3>Sentra Tematik</h3></div>
          <textarea id="tematik" placeholder="Catat pembelajaran tematik minggu ini..."><?= htmlspecialchars($note['tematik']) ?></textarea>
        </div>
      </div>
      <button class="btn btn-primary btn-full" onclick="saveWeekly()">💾 Simpan Catatan</button>
      <div id="wkStatus"></div>
    </div>
    <script>
    function saveWeekly(){
      fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams({action:'save_weekly_note',child_id:'<?= $selChild ?>',week:'<?= $week ?>',
          diniyah:document.getElementById('diniyah').value,
          bilangan_literasi:document.getElementById('bilangan_literasi').value,
          tematik:document.getElementById('tematik').value})
      }).then(()=>{
        document.getElementById('wkStatus').innerHTML='<div class="alert alert-success">✅ Tersimpan!</div>';
        setTimeout(()=>document.getElementById('wkStatus').innerHTML='',3000);
      });
    }
    </script>
    <?php endif; ?>

    <?php
    /* ══════════════════════════════════════════
       PAGE: STARS
    ══════════════════════════════════════════ */
    elseif ($page === 'stars'):
    ?>
    <div class="page-header">
      <h1>⭐ Bintang &amp; Hadiah</h1>
    </div>
    <?php if (empty($children)): ?>
    <div class="empty-state"><p>Tambah data anak dulu</p></div>
    <?php else:
      $selChild  = $_GET['child'] ?? ($children[0]['id']??'');
      $selCD_arr = array_filter($children, fn($c)=>$c['id']===$selChild);
      $selCD     = reset($selCD_arr);
      $ts       = getTotalStars($selChild,$activities);
      $cActs    = array_filter($activities, fn($a)=>$a['child_id']===$selChild);
      usort($cActs, fn($a,$b)=>strcmp($b['date'],$a['date']));
    ?>
    <div class="sel-bar">
      <div class="form-group">
        <label>Pilih Anak</label>
        <select onchange="location.href='?page=stars&child='+this.value">
          <?php foreach($children as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $selChild===$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="star-hero">
      <div class="sh-inner">
        <div class="sh-star">⭐</div>
        <div class="sh-num"><?= $ts ?></div>
        <div class="sh-lbl">Total Bintang <?= htmlspecialchars($selCD['nama']) ?></div>
      </div>
    </div>
    <div class="rewards-grid">
      <?php foreach($settings['hadiah'] as $rw):
        $p=min(100,($ts/$rw['bintang'])*100); $ach=$ts>=$rw['bintang'];
      ?>
      <div class="rw-card <?= $ach?'ach':'' ?>">
        <div class="rw-icon"><?= $ach?'🎁':'🎯' ?></div>
        <div class="rw-name"><?= htmlspecialchars($rw['hadiah']) ?></div>
        <div class="rw-stars"><?= $rw['bintang'] ?> ⭐</div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?= $p ?>%"></div></div>
        <div class="rw-status"><?= $ach?'🎉 Tercapai!':($ts.'/'.$rw['bintang']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <div class="card-header"><h2>📋 Riwayat Kegiatan</h2></div>
      <div class="log-list">
        <?php
        $dg=[];
        foreach($cActs as $a) $dg[$a['date']][]=$a;
        foreach(array_slice($dg,0,14,true) as $dt=>$acts):
        ?>
        <div class="log-day">
          <div class="log-dt"><?= date('d M Y',strtotime($dt)) ?></div>
          <div class="log-items">
            <?php foreach($acts as $a): ?>
            <span class="log-itm"><?= htmlspecialchars($a['item']) ?> <?= str_repeat('⭐',$a['stars']) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="log-tot">+<?= array_sum(array_column($acts,'stars')) ?> ⭐</div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($cActs)): ?><p class="text-muted tc">Belum ada kegiatan</p><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php
    /* ══════════════════════════════════════════
       PAGE: SETTINGS
    ══════════════════════════════════════════ */
    elseif ($page === 'settings'):
    ?>
    <div class="page-header">
      <h1>⚙️ Pengaturan</h1>
    </div>
    <div class="settings-grid">
      <div class="card">
        <div class="card-header"><h2>🎁 Daftar Hadiah</h2></div>
        <div id="hadiahList">
          <?php foreach($settings['hadiah'] as $i=>$h): ?>
          <div class="hadiah-row" data-index="<?= $i ?>">
            <input type="number" class="hi-st" value="<?= $h['bintang'] ?>" min="1"> ⭐ =
            <input type="text" class="hi-nm" value="<?= htmlspecialchars($h['hadiah']) ?>">
            <button class="btn-icon btn-danger" onclick="this.closest('.hadiah-row').remove()">🗑️</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button class="btn btn-ghost btn-full mt8" onclick="addHadiah()">+ Tambah Hadiah</button>
        <button class="btn btn-primary btn-full mt8" onclick="saveHadiah()">💾 Simpan</button>
        <div id="hadiahSt"></div>
      </div>
      <?php
      $editLists = [
        ['id'=>'doaList',    'field'=>'doa',    'title'=>"🤲 Daftar Do'a",    'items'=>$settings['doa']],
        ['id'=>'hadistList', 'field'=>'hadist', 'title'=>'📜 Daftar Hadist',  'items'=>$settings['hadist']],
        ['id'=>'amalanList', 'field'=>'amalan', 'title'=>'✨ Amalan Baik',    'items'=>$settings['amalan']??[]],
      ];
      foreach($editLists as $el):
      ?>
      <div class="card">
        <div class="card-header"><h2><?= $el['title'] ?></h2></div>
        <div id="<?= $el['id'] ?>">
          <?php foreach($el['items'] as $it): ?>
          <div class="list-row">
            <input type="text" value="<?= htmlspecialchars($it) ?>">
            <button class="btn-icon btn-danger" onclick="this.closest('.list-row').remove()">🗑️</button>
          </div>
          <?php endforeach; ?>
        </div>
        <button class="btn btn-ghost btn-full mt8" onclick="addRow('<?= $el['id'] ?>')">+ Tambah</button>
        <button class="btn btn-primary btn-full mt8" onclick="saveList('<?= $el['id'] ?>','<?= $el['field'] ?>')">💾 Simpan</button>
        <div id="<?= $el['field'] ?>St"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <script>
    function addHadiah(){
      const d=document.createElement('div'); d.className='hadiah-row';
      d.innerHTML='<input type="number" class="hi-st" placeholder="⭐" min="1"> ⭐ = <input type="text" class="hi-nm" placeholder="Nama hadiah"> <button class="btn-icon btn-danger" onclick="this.closest(\'.hadiah-row\').remove()">🗑️</button>';
      document.getElementById('hadiahList').appendChild(d);
    }
    function saveHadiah(){
      const items=[...document.querySelectorAll('#hadiahList .hadiah-row')].map(r=>({bintang:parseInt(r.querySelector('.hi-st').value),hadiah:r.querySelector('.hi-nm').value})).filter(h=>h.bintang&&h.hadiah);
      fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'save_settings',field:'hadiah',value:JSON.stringify(items)})})
      .then(()=>{ document.getElementById('hadiahSt').innerHTML='<div class="alert alert-success">✅ Tersimpan!</div>'; setTimeout(()=>document.getElementById('hadiahSt').innerHTML='',2000); });
    }
    function addRow(lid){ const d=document.createElement('div'); d.className='list-row'; d.innerHTML='<input type="text" placeholder="Item baru..."> <button class="btn-icon btn-danger" onclick="this.closest(\'.list-row\').remove()">🗑️</button>'; document.getElementById(lid).appendChild(d); d.querySelector('input').focus(); }
    function saveList(lid,field){
      const items=[...document.querySelectorAll('#'+lid+' input[type=text]')].map(i=>i.value.trim()).filter(Boolean);
      fetch('',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'save_settings',field,value:JSON.stringify(items)})})
      .then(()=>{ document.getElementById(field+'St').innerHTML='<div class="alert alert-success">✅ Tersimpan!</div>'; setTimeout(()=>document.getElementById(field+'St').innerHTML='',2000); });
    }
    </script>
    <?php endif; ?>

    </div><!-- /.page-wrap -->
  </main><!-- /.main-content -->
</div><!-- /.app-layout -->

<!-- ════ BOTTOM NAV (mobile) ════ -->
<nav class="bottom-nav">
  <?php foreach ($navItems as $pg => $n): ?>
  <a href="?page=<?= $pg ?>" class="bnav-item <?= $page===$pg?'active':'' ?>">
    <span class="bnav-icon"><?= $n['icon'] ?></span>
    <span class="bnav-lbl"><?= $n['label'] ?></span>
  </a>
  <?php endforeach; ?>
</nav>

</body>
</html>