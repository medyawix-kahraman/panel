<?php
require_once 'config.php';
$db = getDB();

$settings = [];
foreach ($db->query("SELECT key_name, value FROM settings")->fetchAll() as $r) {
    $settings[$r['key_name']] = $r['value'];
}

$cats = $db->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();

$prods = $db->query("
    SELECT p.*,
        (SELECT filename FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as main_img
    FROM products p WHERE p.active=1 ORDER BY p.category_id, p.sort_order
")->fetchAll();

// Ek görseller
$extraImgs = [];
foreach ($db->query("SELECT product_id, filename FROM product_images WHERE is_primary=0 ORDER BY product_id, sort_order")->fetchAll() as $r) {
    $extraImgs[$r['product_id']][] = UPLOAD_URL . $r['filename'];
}

$banners = $db->query("SELECT * FROM banners WHERE active=1 AND filename IS NOT NULL ORDER BY sort_order")->fetchAll();

// JS için veri hazırla
$jsProds = [];
foreach ($prods as $p) {
    $jsProds[] = [
        'id'          => (int)$p['id'],
        'name'        => $p['name'],
        'code'        => $p['code'] ?? '',
        'catId'       => (int)$p['category_id'],
        'price'       => (float)$p['price'],
        'oldPrice'    => $p['old_price'] ? (float)$p['old_price'] : null,
        'unit'        => $p['unit'] ?? 'adet',
        'badge'       => $p['badge'] ?? '',
        'active'      => true,
        'desc'        => $p['description'] ?? '',
        'img'         => $p['main_img'] ? UPLOAD_URL . $p['main_img'] : '',
        'extraImgs'   => $extraImgs[$p['id']] ?? [],
    ];
}

$jsCats = [];
foreach ($cats as $c) {
    $jsCats[] = ['id' => (int)$c['id'], 'name' => $c['name'], 'active' => true];
}

$jsBanners = [];
foreach ($banners as $b) {
    $jsBanners[] = ['id' => (int)$b['id'], 'img' => UPLOAD_URL . $b['filename'], 'label' => ''];
}

$jsSettings = [
    'brandName'  => $settings['brand_name'] ?? 'DEKOPANEL',
    'brandSub'   => $settings['brand_sub'] ?? 'dekopanel.com.tr',
    'phone'      => $settings['phone'] ?? '',
    'whatsapp'   => $settings['whatsapp'] ?? '',
    'email'      => $settings['email'] ?? '',
    'address'    => $settings['address'] ?? '',
    'instagram'  => $settings['instagram'] ?? '',
    'facebook'   => $settings['facebook'] ?? '',
    'iban'       => $settings['iban'] ?? '',
    'ibanName'   => $settings['iban_name'] ?? '',
    'about'      => $settings['about'] ?? '',
    'logo'       => $settings['logo'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<title><?= htmlspecialchars($jsSettings['brandName']) ?> — Duvar Paneli & Dekorasyon</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--gold:#E8580A;--gd:#C44A08;--gl:#FED7AA;--d:#0A0A0A;--d2:#111;--d3:#181818;--d4:#202020;--d5:#2A2A2A;--t:#E0D8CC;--m:#7A7060;--w:#F5F0E8;--red:#B52A1A;}
*{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{background:var(--d);color:var(--t);font-family:'DM Sans',sans-serif;overflow-x:hidden;}
a{text-decoration:none;color:inherit;}
img{max-width:100%;display:block;}
button{font-family:'DM Sans',sans-serif;}

/* TICKER */
.ticker{background:linear-gradient(90deg,#14100a,#1e1508,#14100a);padding:9px 0;overflow:hidden;border-bottom:1px solid rgba(232,88,10,.18);}
.ticker-t{display:flex;width:max-content;animation:tick 40s linear infinite;}
.ticker-t span{white-space:nowrap;padding:0 44px;font-size:11.5px;font-weight:500;color:var(--gl);letter-spacing:.8px;}
.ticker-t span::before{content:"◆";margin-right:14px;font-size:7px;opacity:.6;}
@keyframes tick{0%{transform:translateX(0);}100%{transform:translateX(-50%);}}

/* HEADER */
header{background:rgba(10,10,10,.98);backdrop-filter:blur(12px);border-bottom:1px solid rgba(232,88,10,.1);position:sticky;top:0;z-index:1000;}
.hdr{display:flex;align-items:center;justify-content:space-between;padding:0 40px;height:80px;gap:20px;}
.logo-a{display:flex;align-items:center;gap:12px;flex-shrink:0;cursor:pointer;}
.logo-fb{display:flex;flex-direction:column;}
.logo-fb .bn{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--gold);letter-spacing:3px;line-height:1;}
.logo-fb .bs{font-size:8.5px;letter-spacing:4px;color:var(--m);text-transform:uppercase;margin-top:4px;}
.hdr-srch{flex:1;max-width:440px;display:flex;align-items:stretch;background:var(--d3);border:1px solid rgba(232,88,10,.16);}
.hdr-srch input{flex:1;background:transparent;border:none;outline:none;padding:0 16px;color:var(--t);font-size:13px;min-width:0;}
.hdr-srch input::placeholder{color:var(--m);}
.hdr-srch button{background:var(--gold);border:none;padding:0 15px;cursor:pointer;color:#0A0A0A;display:flex;align-items:center;}
.hdr-srch svg{width:15px;height:15px;display:block;}
.hdr-r{display:flex;align-items:center;gap:14px;}
.hl{color:var(--m);font-size:12.5px;display:flex;align-items:center;gap:6px;transition:color .3s;cursor:pointer;}
.hl:hover{color:var(--gold);}
.wa-hl{background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff!important;padding:9px 15px;font-weight:600;font-size:12px!important;border-radius:3px;}
.mob-btn{display:none;background:none;border:none;color:var(--t);cursor:pointer;padding:6px;}

/* HEADER CART BUTTON */
.cart-hdr-btn {
  background: transparent;
  border: 1px solid rgba(232,88,10,.25);
  color: var(--t);
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}
.cart-hdr-btn:hover {
  border-color: var(--gold);
  color: var(--gold);
  background: rgba(232,88,10,.05);
}
.cart-hdr-btn svg {
  width: 15px;
  height: 15px;
}
.cart-hdr-badge {
  background: var(--gold);
  color: #0A0A0A;
  font-size: 10px;
  font-weight: 700;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* NAV */
.dsk-nav{background:var(--d2);border-bottom:1px solid rgba(232,88,10,.07);}
.nav-in{display:flex;align-items:center;justify-content:center;padding:0 40px;overflow-x:auto;scrollbar-width:none;}
.nav-in::-webkit-scrollbar{display:none;}
.nav-in a{color:#D4CCC0;padding:12px 14px;font-size:11.5px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;transition:color .3s;position:relative;white-space:nowrap;cursor:pointer;}
.nav-in a:hover,.nav-in a.act{color:var(--gold);}
.nav-in a.act::after{content:'';position:absolute;bottom:0;left:14px;right:14px;height:2px;background:var(--gold);}
.mob-nav{display:none;background:var(--d2);border-top:1px solid rgba(232,88,10,.08);}
.mob-nav a{display:block;padding:12px 20px;font-size:13px;color:var(--t);border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer;}

/* SLIDER */
.slider-w{position:relative;overflow:hidden;background:var(--d2);}
.slides-t{display:flex;transition:transform .55s cubic-bezier(.4,0,.2,1);}
.slide{min-width:100%;position:relative;aspect-ratio:12/5;overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--d3);}
.slide img{width:100%;height:100%;object-fit:cover;display:block;}
.s-arr{position:absolute;top:50%;transform:translateY(-50%);background:rgba(10,10,10,.6);border:1px solid rgba(232,88,10,.2);color:var(--gold);width:42px;height:42px;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:5;transition:.3s;}
.s-arr:hover{background:var(--gold);color:#0A0A0A;}
.s-prev{left:16px;}.s-next{right:16px;}
.s-dots{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:5;}
.s-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.3);cursor:pointer;transition:.3s;}
.s-dot.on{background:var(--gold);width:22px;border-radius:4px;}

/* KATEGORİ */
.cat-bar{background:var(--d2);border-bottom:1px solid rgba(255,255,255,.05);position:sticky;top:80px;z-index:99;}
.cat-bar-in{display:flex;gap:6px;padding:10px 40px;overflow-x:auto;scrollbar-width:none;}
.cat-bar-in::-webkit-scrollbar{display:none;}
.cb{background:transparent;border:1px solid rgba(232,88,10,.2);color:var(--m);padding:7px 16px;font-size:11.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;cursor:pointer;transition:.3s;white-space:nowrap;}
.cb:hover{border-color:var(--gold);color:var(--gold);}
.cb.on{background:var(--gold);border-color:var(--gold);color:#0A0A0A;}

/* ÜRÜN GRID */
.sec{padding:40px;}
.sec-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
.sec-hd h2{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--w);}
.pg{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}

/* ÜRÜN KARTI */
.pc{background:var(--d3);border:1px solid rgba(255,255,255,.04);border-radius:12px;overflow:hidden;cursor:pointer;transition:all .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;justify-content:space-between;}
.pc:hover{border-color:rgba(232,88,10,.35);transform:translateY(-6px);box-shadow:0 12px 24px rgba(232,88,10,.12);}
.pc-img{height:250px;background:#141416;overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center;border-bottom:1px solid rgba(255,255,255,0.03);}
.pc-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease;}
.pc:hover .pc-img img{transform:scale(1.05);}
.pc-no{color:rgba(232,88,10,.08);display:flex;align-items:center;justify-content:center;width:100%;height:100%;}
.pc-bdg{position:absolute;top:10px;left:10px;font-size:9px;font-weight:700;padding:4px 9px;letter-spacing:.8px;text-transform:uppercase;border-radius:2px;z-index:2;}
.bn{background:var(--gold);color:#0A0A0A;}.bs{background:var(--d5);color:var(--gold);border:1px solid rgba(232,88,10,.3);}
.pc-body{padding:18px;display:flex;flex-direction:column;flex:1;}
.pc-body h3{font-size:13.5px;font-weight:500;color:var(--t);letter-spacing:.3px;line-height:1.45;margin-bottom:8px;min-height:40px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.pc-code{font-size:10px;color:var(--m);margin-bottom:10px;letter-spacing:.5px;text-transform:uppercase;}
.pc-price-wrap{display:flex;align-items:baseline;gap:6px;margin-bottom:2px;}
.pc-price{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--gold);}
.pc-old{font-size:13px;color:var(--m);text-decoration:line-through;}
.pc-unit{font-size:10.5px;color:var(--m);margin-bottom:14px;}
.pc-foot{display:flex;gap:8px;margin-top:auto;}
.pc-btn{flex:1;padding:10px 8px;font-size:10.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;cursor:pointer;transition:.3s;border:none;border-radius:4px;display:flex;align-items:center;justify-content:center;gap:6px;}
.pc-i{background:transparent;border:1px solid rgba(255,255,255,.1);color:var(--m);}
.pc-i:hover{border-color:var(--gold);color:var(--gold);background:rgba(232,88,10,.05);}
.pc-q{background:linear-gradient(135deg,var(--gold),var(--gd));color:#0A0A0A;font-weight:700;}
.pc-q:hover{opacity:.9;transform:scale(1.02);}

/* MODAL */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:2000;display:none;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(6px);}
.modal-bg.open{display:flex;}
.modal-box{background:var(--d2);width:min(860px,96vw);max-height:92vh;overflow-y:auto;border:1px solid rgba(232,88,10,.12);border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,.6);}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid rgba(255,255,255,.06);position:sticky;top:0;background:var(--d2);z-index:1;}
.modal-hd h2{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--w);font-weight:600;}
.modal-cls{background:none;border:none;color:var(--m);font-size:20px;cursor:pointer;padding:2px 6px;transition:color .2s;}
.modal-cls:hover{color:var(--gold);}
.modal-body{display:grid;grid-template-columns:1fr 1fr;}
.modal-gallery{background:var(--d3);padding:24px;border-right:1px solid rgba(255,255,255,.04);}
.modal-main-img{aspect-ratio:1;overflow:hidden;background:#141416;display:flex;align-items:center;justify-content:center;margin-bottom:12px;border:1px solid rgba(255,255,255,.03);}
.modal-main-img img{width:100%;height:100%;object-fit:cover;}
.modal-thumbs{display:flex;gap:8px;flex-wrap:wrap;}
.modal-thumb{width:62px;height:62px;overflow:hidden;border:2px solid transparent;border-radius:4px;cursor:pointer;background:#141416;}
.modal-thumb:hover,.modal-thumb.act{border-color:var(--gold);}
.modal-thumb img{width:100%;height:100%;object-fit:cover;}
.modal-info{padding:24px;display:flex;flex-direction:column;gap:12px;}
.m-name{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;color:var(--w);line-height:1.25;}
.m-code{font-size:11px;color:var(--m);letter-spacing:1px;text-transform:uppercase;}
.m-price{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--gold);}
.m-old{font-size:16px;color:var(--m);text-decoration:line-through;margin-left:8px;}
.m-unit{font-size:12px;color:var(--m);margin-top:-6px;}
.m-desc{font-size:13.5px;line-height:1.75;color:var(--t);margin-top:6px;white-space:pre-wrap;}
.m-desc ul,.m-desc ol{padding-left:18px;}
.wa-big{width:100%;margin-top:auto;padding:14px;background:linear-gradient(135deg,var(--gold),var(--gd));color:#0A0A0A;border:none;font-size:13px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:.3s;border-radius:4px;}
.wa-big:hover{opacity:.9;transform:scale(1.01);}
.wa-secondary {
  background: transparent;
  border: 1px solid rgba(34,197,94,0.4);
  color: #22c55e!important;
  margin-top: 8px;
}
.wa-secondary:hover {
  background: rgba(34,197,94,0.05);
  border-color: #22c55e;
}

/* BANKA/IBAN */
.pg-hd{padding:32px 40px 0;border-bottom:1px solid rgba(255,255,255,.05);margin-bottom:0;}
.pg-bc{font-size:12px;color:var(--m);margin-bottom:8px;}
.pg-bc a{color:var(--m);cursor:pointer;} .pg-bc a:hover{color:var(--gold);}
.pg-hd h1{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--w);padding-bottom:20px;}
.pg-body{padding:32px 40px;}
.bank{background:var(--d3);border:1px solid rgba(232,88,10,.12);padding:20px 24px;margin-bottom:12px;}
.bank strong{display:block;font-size:13px;color:var(--gold);margin-bottom:6px;letter-spacing:.5px;}
.bank p{font-size:13px;color:var(--t);line-height:1.8;}
.iban-val{font-size:16px;font-weight:700;color:var(--w);letter-spacing:2px;margin-top:4px;}

/* SHOPPING CART DRAWER */
.cart-drawer-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  z-index: 3000;
  display: none;
  justify-content: flex-end;
}
.cart-drawer-backdrop.open {
  display: flex;
}
.cart-drawer {
  width: min(440px, 100vw);
  height: 100%;
  background: var(--d2);
  border-left: 1px solid rgba(232, 88, 10, 0.15);
  display: flex;
  flex-direction: column;
  box-shadow: -10px 0 30px rgba(0,0,0,0.5);
  transform: translateX(100%);
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
.cart-drawer-backdrop.open .cart-drawer {
  transform: translateX(0);
}
.cart-drawer-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.cart-drawer-hd h3 {
  font-family: 'Cormorant Garamond', serif;
  font-size: 22px;
  color: var(--w);
  font-weight: 600;
}
.cart-drawer-cls {
  background: none;
  border: none;
  color: var(--m);
  font-size: 22px;
  cursor: pointer;
  padding: 4px;
  transition: color 0.2s;
}
.cart-drawer-cls:hover {
  color: var(--gold);
}
.cart-drawer-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}
.cart-item {
  display: flex;
  gap: 14px;
  align-items: center;
  padding-bottom: 18px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.03);
}
.cart-item-img {
  width: 64px;
  height: 64px;
  border-radius: 6px;
  overflow: hidden;
  background: #141416;
  flex-shrink: 0;
}
.cart-item-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.cart-item-info {
  flex: 1;
  min-width: 0;
}
.cart-item-name {
  font-size: 13.5px;
  font-weight: 500;
  color: var(--t);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 3px;
}
.cart-item-code {
  font-size: 10px;
  color: var(--m);
  margin-bottom: 6px;
  text-transform: uppercase;
}
.cart-item-price {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--gold);
}
.cart-item-ctrl {
  display: flex;
  align-items: center;
  gap: 8px;
}
.cart-qty-btn {
  width: 24px;
  height: 24px;
  background: var(--d4);
  border: 1px solid rgba(255,255,255,0.06);
  color: var(--t);
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}
.cart-qty-btn:hover {
  background: var(--gold);
  color: #0A0A0A;
  border-color: var(--gold);
}
.cart-item-qty {
  font-size: 13px;
  font-weight: 600;
  min-width: 22px;
  text-align: center;
}
.cart-item-del {
  background: none;
  border: none;
  color: var(--m);
  cursor: pointer;
  padding: 4px;
  font-size: 14px;
  transition: color 0.2s;
}
.cart-item-del:hover {
  color: var(--red);
}
.cart-drawer-ft {
  padding: 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  background: var(--d3);
}
.cart-summary-line {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: var(--m);
  margin-bottom: 8px;
}
.total-price-line {
  font-size: 19px;
  font-weight: 700;
  color: var(--w);
  margin-top: 12px;
  margin-bottom: 20px;
  border-top: 1px dashed rgba(255,255,255,0.08);
  padding-top: 12px;
}
.total-price-line span:last-child {
  color: var(--gold);
}
.checkout-btn {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--gold), var(--gd));
  border: none;
  color: #0A0A0A;
  font-weight: 700;
  font-size: 13px;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  cursor: pointer;
  transition: opacity 0.2s;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.checkout-btn:hover {
  opacity: 0.9;
}
.cart-empty-msg {
  text-align: center;
  color: var(--m);
  font-size: 13.5px;
  padding: 60px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

/* CHECKOUT LAYOUT */
.checkout-layout {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 40px;
}
.co-form-panel {
  background: var(--d3);
  padding: 28px;
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 8px;
}
.co-form-panel label {
  display: block;
  font-size: 12px;
  color: var(--m);
  margin-bottom: 6px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.co-input {
  width: 100%;
  background: var(--d4);
  border: 1px solid rgba(255,255,255,0.08);
  color: var(--t);
  padding: 12px 16px;
  font-size: 13px;
  border-radius: 4px;
  outline: none;
  transition: border-color 0.2s;
}
.co-input:focus {
  border-color: var(--gold);
}
.co-form-group {
  margin-bottom: 20px;
}
.co-sidebar {
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.co-box {
  background: var(--d3);
  padding: 24px;
  border: 1px solid rgba(255,255,255,0.04);
  border-radius: 8px;
}
.co-box-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 22px;
  color: var(--w);
  font-weight: 600;
  margin-bottom: 18px;
  border-bottom: 1px solid rgba(255,255,255,0.04);
  padding-bottom: 8px;
}
.co-summary-item {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
  padding-bottom: 10px;
  margin-bottom: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.03);
}
.co-summary-total {
  display: flex;
  justify-content: space-between;
  font-size: 16px;
  font-weight: 700;
  color: var(--w);
  border-top: 1px dashed rgba(255,255,255,0.08);
  padding-top: 16px;
  margin-top: 14px;
}
.co-summary-total-price {
  color: var(--gold);
  font-size: 21px;
}
.co-iban-box {
  background: var(--d2);
  border: 1px solid rgba(232,88,10,0.12);
  padding: 16px;
  border-radius: 6px;
  position: relative;
  cursor: pointer;
}
.co-iban-val {
  font-family: monospace;
  font-size: 14px;
  font-weight: 700;
  color: var(--w);
  letter-spacing: 1px;
  background: var(--d4);
  padding: 10px;
  border-radius: 4px;
  margin-top: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.co-iban-val span:last-child {
  font-size: 10px;
  color: var(--gold);
  font-weight: 700;
  text-transform: uppercase;
}

/* FOOTER */
footer{background:var(--d2);border-top:1px solid rgba(232,88,10,.08);padding:48px 40px 24px;}
.ft-grid{display:grid;grid-template-columns:2fr 1fr 1fr;gap:48px;margin-bottom:40px;}
.ft-brand{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:700;color:var(--gold);letter-spacing:3px;margin-bottom:10px;}
.ft-desc{font-size:13px;color:var(--m);line-height:1.8;margin-bottom:16px;}
.ft-soc{display:flex;gap:10px;}
.ft-soc a{width:34px;height:34px;border:1px solid rgba(232,88,10,.2);display:flex;align-items:center;justify-content:center;color:var(--m);font-size:14px;transition:.3s;}
.ft-soc a:hover{border-color:var(--gold);color:var(--gold);}
.ft-col h4{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:14px;}
.ft-col a,.ft-col p{display:block;font-size:13px;color:var(--m);line-height:2.2;cursor:pointer;transition:color .3s;}
.ft-col a:hover{color:var(--t);}
.ft-bot{border-top:1px solid rgba(255,255,255,.05);padding-top:20px;display:flex;align-items:center;justify-content:space-between;font-size:11.5px;color:var(--m);}
.ft-bot a{color:var(--gold);}

/* WİXMEDYA */
.wix-sig{background:#080808;text-align:center;padding:10px;font-size:11px;color:#444;}
.wix-sig a{color:var(--gold);}

/* SEARCH DROP */
.srch-drop{position:absolute;top:80px;left:50%;transform:translateX(-50%);width:440px;background:var(--d3);border:1px solid rgba(232,88,10,.2);z-index:999;display:none;max-height:320px;overflow-y:auto;}
.srch-drop.open{display:block;}
.srch-item{display:flex;gap:12px;padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.04);cursor:pointer;align-items:center;}
.srch-item:hover{background:var(--d4);}
.srch-item img{width:42px;height:42px;object-fit:cover;background:var(--d4);}
.srch-item .si-name{font-size:12.5px;color:var(--t);}
.srch-item .si-price{font-size:12px;color:var(--gold);}

/* MOBİL */
@media(max-width:900px){
  .hdr{padding:0 16px;height:64px;}
  .hdr-srch{display:none;}
  .mob-btn{display:flex;}
  .dsk-nav{display:none;}
  .cat-bar{top:64px;}
  .cat-bar-in{padding:8px 12px;}
  .sec{padding:20px 12px;}
  .pg{grid-template-columns:repeat(2,1fr);gap:10px;}
  .modal-body{grid-template-columns:1fr;}
  .modal-gallery{border-right:none;border-bottom:1px solid rgba(255,255,255,.04);}
  .ft-grid{grid-template-columns:1fr;gap:24px;}
  .ft-bot{flex-direction:column;gap:6px;text-align:center;}
  .checkout-layout {grid-template-columns:1fr!important;gap:24px;}
  .cart-hdr-btn {padding: 6px 10px; font-size: 11px;}
  .cart-hdr-btn span span {display: none;}
}
@media(max-width:480px){
  .pg{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<!-- TICKER -->
<div class="ticker"><div class="ticker-t" id="tickerT">
  <span>Bayimiz Olmak İster Misiniz?</span><span>Türkiye Genelinde Hızlı Kargo</span><span>10.000+ Farklı Ürün Tasarımı</span><span>Toptan Satış İmkânı</span><span>MDF Panel</span><span>Lambri</span><span>Akustik Panel</span><span>Duvar Çıtası</span><span>Süpürgelik</span><span>Osmanlı Motifler</span>
  <span>Bayimiz Olmak İster Misiniz?</span><span>Türkiye Genelinde Hızlı Kargo</span><span>10.000+ Farklı Ürün Tasarımı</span><span>Toptan Satış İmkânı</span><span>MDF Panel</span><span>Lambri</span><span>Akustik Panel</span><span>Duvar Çıtası</span><span>Süpürgelik</span><span>Osmanlı Motifler</span>
</div></div>

<header>
  <div class="hdr">
    <div class="logo-a" onclick="goHome()">
      <?php if($jsSettings['logo']): ?>
        <img src="/uploads/<?= htmlspecialchars($jsSettings['logo']) ?>" alt="<?= htmlspecialchars($jsSettings['brandName']) ?>" style="max-height:52px;max-width:180px;object-fit:contain;">
      <?php else: ?>
      <div class="logo-fb">
        <span class="bn" id="bName"><?= htmlspecialchars($jsSettings['brandName']) ?></span>
        <span class="bs" id="bSub"><?= htmlspecialchars($jsSettings['brandSub']) ?></span>
      </div>
      <?php endif; ?>
    </div>
    <div class="hdr-srch">
      <input type="text" id="srchInp" placeholder="Ürün adı veya stok kodu..." autocomplete="off" oninput="liveSearch(this.value)" onblur="setTimeout(closeSrch,200)">
      <button onclick="doSearch()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
      </button>
    </div>
    <div class="hdr-r">
      <!-- SHOPPING CART TRIGGER -->
      <button class="cart-hdr-btn" onclick="toggleCart()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        <span>Sepetim </span>
        <span class="cart-hdr-badge" id="cartCount">0</span>
      </button>

      <a id="waHdr" class="hl wa-hl" href="https://wa.me/<?= htmlspecialchars($jsSettings['whatsapp']) ?>" target="_blank">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413"/></svg>
        WhatsApp
      </a>
      <span class="hl" onclick="goPage('contact')">İletişim</span>
      <button class="mob-btn" onclick="toggleMob()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
  <nav class="dsk-nav"><div class="nav-in" id="navIn"></div></nav>
  <div class="mob-nav" id="mobNav"></div>
</header>

<div class="srch-drop" id="srchDrop"></div>
<main id="main"></main>

<!-- SHOPPING CART DRAWER HTML -->
<div class="cart-drawer-backdrop" id="cartDrawerBackdrop" onclick="if(event.target===this)toggleCart(false)">
  <div class="cart-drawer">
    <div class="cart-drawer-hd">
      <h3>Alışveriş Sepetim</h3>
      <button class="cart-drawer-cls" onclick="toggleCart(false)">✕</button>
    </div>
    <div class="cart-drawer-body" id="cartDrawerBody"></div>
    <div class="cart-drawer-ft" id="cartDrawerFt">
      <div class="cart-summary-line">
        <span>Toplam Ürün:</span>
        <span id="cartSummaryQty">0 Adet</span>
      </div>
      <div class="cart-summary-line total-price-line">
        <span>Genel Toplam:</span>
        <span id="cartSummaryTotal">0.00 ₺</span>
      </div>
      <button class="checkout-btn" onclick="openCheckout()">
        Siparişi Tamamla (Havale)
      </button>
    </div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-bg" id="prodModal" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div class="modal-hd"><h2>Ürün Detayı</h2><button class="modal-cls" onclick="closeModal()">✕</button></div>
    <div class="modal-body" id="modalBody"></div>
  </div>
</div>

<footer id="ftEl"></footer>
<div class="wix-sig">Web Tasarım: <a href="https://wixmedya.com" target="_blank">WixMedya — Reklam ve Web Hizmetleri</a></div>

<script>
var _PRODS   = <?= json_encode($jsProds, JSON_UNESCAPED_UNICODE) ?>;
var _CATS    = <?= json_encode($jsCats, JSON_UNESCAPED_UNICODE) ?>;
var _BANNERS = <?= json_encode($jsBanners, JSON_UNESCAPED_UNICODE) ?>;
var _SETTINGS= <?= json_encode($jsSettings, JSON_UNESCAPED_UNICODE) ?>;

var SLIDES = [
  {l:'MDF Panel Koleksiyonu'},
  {l:'Akustik Yalıtım Panelleri'},
  {l:'Osmanlı Motifler'},
  {l:'Duvar Çıtası Serisi'}
];

// CART STATE
var CART = [];

function S(){ return _SETTINGS; }
function C(){ return _CATS; }
function P(){ return _PRODS; }
function BN(){ return _BANNERS; }

// LOCAL STORAGE CART OPERATIONS
function loadCart() {
  try {
    var stored = localStorage.getItem('dekopanel_cart');
    if (stored) {
      CART = JSON.parse(stored);
    }
  } catch(e) {
    CART = [];
  }
}

function saveCart() {
  localStorage.setItem('dekopanel_cart', JSON.stringify(CART));
}

function addToCart(id) {
  var p = P().find(function(x){return x.id===id;});
  if (!p) return;
  var existing = CART.find(function(x){return x.id===id;});
  if (existing) {
    existing.qty += 1;
  } else {
    CART.push({id: id, qty: 1});
  }
  saveCart();
  updateCartUI();
  toggleCart(true); // Open drawer automatically
}

function updateQty(id, delta) {
  var existing = CART.find(function(x){return x.id===id;});
  if (!existing) return;
  existing.qty += delta;
  if (existing.qty <= 0) {
    CART = CART.filter(function(x){return x.id!==id;});
  }
  saveCart();
  updateCartUI();
}

function removeFromCart(id) {
  CART = CART.filter(function(x){return x.id!==id;});
  saveCart();
  updateCartUI();
}

function getCartTotal() {
  return CART.reduce(function(acc, item) {
    var p = P().find(function(x){return x.id===item.id;});
    var price = p ? p.price : 0;
    return acc + (price * item.qty);
  }, 0);
}

function getCartQty() {
  return CART.reduce(function(acc, item){return acc + item.qty;}, 0);
}

function updateCartUI() {
  var qty = getCartQty();
  var total = getCartTotal();
  
  var countEl = document.getElementById('cartCount');
  if (countEl) countEl.textContent = qty;
  
  var summaryQtyEl = document.getElementById('cartSummaryQty');
  if (summaryQtyEl) summaryQtyEl.textContent = qty + " Adet";
  
  var summaryTotalEl = document.getElementById('cartSummaryTotal');
  if (summaryTotalEl) summaryTotalEl.textContent = total.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + " ₺";
  
  var bodyEl = document.getElementById('cartDrawerBody');
  var ftEl = document.getElementById('cartDrawerFt');
  if (!bodyEl) return;
  
  if (CART.length === 0) {
    bodyEl.innerHTML = '<div class="cart-empty-msg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48" style="opacity:0.3; margin-bottom:12px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>Sepetiniz şu anda boş.</div>';
    if (ftEl) ftEl.style.display = 'none';
  } else {
    if (ftEl) ftEl.style.display = 'block';
    bodyEl.innerHTML = CART.map(function(item) {
      var p = P().find(function(x){return x.id===item.id;});
      if (!p) return '';
      var imgH = p.img && p.img.length > 10 ? '<img src="'+p.img+'" alt="">' : '<div style="width:100%;height:100%;background:var(--d5);display:flex;align-items:center;justify-content:center;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="20" height="20"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
      return '<div class="cart-item">' +
        '<div class="cart-item-img">' + imgH + '</div>' +
        '<div class="cart-item-info">' +
          '<div class="cart-item-name">' + esc(p.name) + '</div>' +
          '<div class="cart-item-code">' + esc(p.code) + '</div>' +
          '<div class="cart-item-price">' + p.price.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺</div>' +
        '</div>' +
        '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">' +
          '<button class="cart-item-del" onclick="removeFromCart(' + p.id + ')">✕</button>' +
          '<div class="cart-item-ctrl">' +
            '<button class="cart-qty-btn" onclick="updateQty(' + p.id + ', -1)">-</button>' +
            '<span class="cart-item-qty">' + item.qty + '</span>' +
            '<button class="cart-qty-btn" onclick="updateQty(' + p.id + ', 1)">+</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join('');
  }
}

function toggleCart(open) {
  var b = document.getElementById('cartDrawerBackdrop');
  if (!b) return;
  if (open === true) {
    b.classList.add('open');
    document.body.style.overflow = 'hidden';
  } else if (open === false) {
    b.classList.remove('open');
    document.body.style.overflow = '';
  } else {
    var isOpen = b.classList.contains('open');
    b.classList.toggle('open');
    document.body.style.overflow = isOpen ? '' : 'hidden';
  }
}

function openCheckout() {
  toggleCart(false);
  goPage('checkout');
}

function copyIban() {
  var s = S();
  navigator.clipboard.writeText(s.iban).then(function() {
    alert("IBAN Kopyalandı!");
  }).catch(function() {
    alert("Kopyalanamadı, lütfen manuel kopyalayın.");
  });
}

// SLIDER OPERATIONS
var si=0, stimer=null;
function startSlider(){
  si=0;
  if(stimer) clearInterval(stimer);
  if(SLIDES.length>1) stimer=setInterval(function(){sMove(1);},4500);
}
function sMove(d){ sTo((si+d+SLIDES.length)%SLIDES.length); }
function sTo(i){
  si=i;
  var t=document.getElementById('slidesT');
  if(t) t.style.transform='translateX(-'+i*100+'%)';
  document.querySelectorAll('.s-dot').forEach(function(d,j){ d.classList.toggle('on',j===i); });
  var banners=BN();
  var slide=document.getElementById('sl'+i);
  if(slide && banners[i] && banners[i].img){
    var existing=slide.querySelector('img');
    if(!existing){
      var img=document.createElement('img');
      img.src=banners[i].img;
      img.alt='';
      slide.insertBefore(img,slide.firstChild);
    }
  }
}

function buildNav(){
  var cats=C();
  var html='<a onclick="goHome()" class="act" id="nav-all">Tümü</a>';
  cats.forEach(function(c){
    html+='<a onclick="goCat('+c.id+')" id="nav-'+c.id+'">'+c.name+'</a>';
  });
  html+='<a onclick="goPage(\'bank\')">Banka Hesapları</a>';
  html+='<a onclick="goPage(\'contact\')">İletişim</a>';
  document.getElementById('navIn').innerHTML=html;

  var mob='<a onclick="goHome();toggleMob()">Tümü</a>';
  cats.forEach(function(c){
    mob+='<a onclick="goCat('+c.id+');toggleMob()">'+c.name+'</a>';
  });
  mob+='<a onclick="goPage(\'bank\');toggleMob()">Banka Hesapları</a>';
  mob+='<a onclick="goPage(\'contact\');toggleMob()">İletişim</a>';
  document.getElementById('mobNav').innerHTML=mob;
}
function toggleMob(){
  var m=document.getElementById('mobNav');
  m.style.display=m.style.display==='block'?'none':'block';
}

function goHome(){
  var prods=P().filter(function(p){return p.active;});
  var cats=C();
  var slideHtml=SLIDES.map(function(sl,i){
    var b=BN()[i];
    var imgHtml=b&&b.img?'<img src="'+b.img+'" alt="" loading="lazy">':'';
    return '<div class="slide" id="sl'+i+'">'+imgHtml+'</div>';
  }).join('');
  var dotsHtml=SLIDES.map(function(_,i){return '<div class="s-dot'+(i===0?' on':'')+'" onclick="sTo('+i+')"></div>';}).join('');

  var catBar='<div class="cb on" onclick="filterGrid(0)" id="cb-all">Tümü ('+prods.length+')</div>';
  cats.forEach(function(c){
    var cnt=prods.filter(function(p){return p.catId===c.id;}).length;
    catBar+='<div class="cb" onclick="filterGrid('+c.id+')" id="cb-'+c.id+'">'+c.name+' ('+cnt+')</div>';
  });

  document.getElementById('main').innerHTML=
    '<div class="slider-w">'+
      '<div class="slides-t" id="slidesT">'+slideHtml+'</div>'+
      (SLIDES.length>1?'<button class="s-arr s-prev" onclick="sMove(-1)">&#8249;</button><button class="s-arr s-next" onclick="sMove(1)">&#8250;</button>':'')+
      '<div class="s-dots">'+dotsHtml+'</div>'+
    '</div>'+
    '<div class="cat-bar"><div class="cat-bar-in" id="catBarIn">'+catBar+'</div></div>'+
    '<div class="sec"><div class="sec-hd"><h2 id="secTitle">Tüm Ürünler</h2></div><div class="pg" id="prodGrid"></div></div>';

  renderGrid(prods);
  startSlider();
  setNav('nav-all');
}

function filterGrid(catId){
  var prods=P().filter(function(p){return p.active&&(catId===0||p.catId===catId);});
  document.querySelectorAll('.cb').forEach(function(b){b.classList.remove('on');});
  var btn=document.getElementById(catId?'cb-'+catId:'cb-all');
  if(btn) btn.classList.add('on');
  var cat=C().find(function(c){return c.id===catId;});
  var title=document.getElementById('secTitle');
  if(title) title.textContent=cat?cat.name:'Tüm Ürünler';
  renderGrid(prods);
}

function renderGrid(prods){
  var g=document.getElementById('prodGrid');
  if(!g) return;
  g.innerHTML=prods.map(function(p){ return pCard(p); }).join('') ||
    '<p style="color:var(--m);padding:40px;grid-column:1/-1;text-align:center">Bu kategoride ürün bulunamadı.</p>';
}

function pCard(p){
  var imgH=p.img&&p.img.length>10?'<img src="'+p.img+'" alt="'+esc(p.name)+'" loading="lazy">':
    '<div class="pc-no"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" width="52" height="52"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
  var badgeH=p.badge?'<div class="pc-bdg '+(p.badge==='Yeni'?'bn':'bs')+'">'+p.badge+'</div>':'';
  var oldH=p.oldPrice?'<span class="pc-old">'+p.oldPrice.toLocaleString('tr-TR', {minimumFractionDigits:2})+' ₺</span>':'';
  return '<div class="pc" onclick="openModal('+p.id+')">'+
    '<div class="pc-img">'+imgH+badgeH+'</div>'+
    '<div class="pc-body">'+
      '<h3>'+esc(p.name)+'</h3>'+
      '<div class="pc-code">'+esc(p.code)+'</div>'+
      '<div class="pc-price-wrap">'+
        '<div class="pc-price">'+p.price.toLocaleString('tr-TR', {minimumFractionDigits:2})+' ₺</div>'+
        oldH+
      '</div>'+
      '<div class="pc-unit">/ 1 '+esc(p.unit)+'</div>'+
      '<div class="pc-foot">'+
        '<button class="pc-btn pc-i" onclick="event.stopPropagation();openModal('+p.id+')">İncele</button>'+
        '<button class="pc-btn pc-q" onclick="event.stopPropagation();addToCart('+p.id+')"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg> Sepete Ekle</button>'+
      '</div>'+
    '</div></div>';
}

function openModal(id){
  var p=P().find(function(x){return x.id===id;}); if(!p) return;
  var wa=S().whatsapp.replace(/\D/g,'');
  var msg=encodeURIComponent('Merhaba, '+p.name+' ('+p.code+') ürünü hakkında detaylı bilgi almak istiyorum.');
  var allImgs=[p.img].concat(p.extraImgs||[]).filter(function(x){return x&&x.length>5;});
  var mainImg=allImgs[0]||'';

  var galleryHtml='<div class="modal-gallery">'+
    '<div class="modal-main-img" id="modalMainImg">'+
      (mainImg?'<img src="'+mainImg+'" alt="'+esc(p.name)+'" id="mainImgEl">':
        '<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="rgba(232,88,10,.1)" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>')+
    '</div>';
  if(allImgs.length>1){
    galleryHtml+='<div class="modal-thumbs">';
    allImgs.forEach(function(img,i){
      galleryHtml+='<div class="modal-thumb'+(i===0?' act':'')+'" onclick="switchImg(\''+img+'\',this)"><img src="'+img+'" alt=""></div>';
    });
    galleryHtml+='</div>';
  }
  galleryHtml+='</div>';

  var descHtml=p.desc?p.desc.replace(/\n/g,'<br>'):'';

  var infoHtml='<div class="modal-info">'+
    (p.badge?'<div style="display:inline-block;background:var(--gold);color:#0A0A0A;font-size:9px;font-weight:700;padding:3px 9px;letter-spacing:.8px;text-transform:uppercase;margin-bottom:4px;width:max-content;border-radius:2px;">'+p.badge+'</div>':'')+
    '<div class="m-name">'+esc(p.name)+'</div>'+
    '<div class="m-code">Stok Kodu: '+esc(p.code)+'</div>'+
    '<div class="m-price">'+p.price.toLocaleString('tr-TR', {minimumFractionDigits:2})+' ₺'+(p.oldPrice?'<span class="m-old">'+p.oldPrice.toLocaleString('tr-TR', {minimumFractionDigits:2})+' ₺</span>':'')+'</div>'+
    '<div class="m-unit">Birim: 1 '+esc(p.unit)+'</div>'+
    '<div class="m-desc">'+descHtml+'</div>'+
    '<button class="wa-big" onclick="addToCart('+p.id+'); closeModal();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14" style="display:inline-block;vertical-align:middle;margin-right:6px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg> Sepete Ekle</button>'+
    '<a class="wa-big wa-secondary" href="https://wa.me/'+wa+'?text='+msg+'" target="_blank">'+waIcon()+' WhatsApp İle Bilgi Al</a>'+
  '</div>';

  document.getElementById('modalBody').innerHTML=galleryHtml+infoHtml;
  document.getElementById('prodModal').classList.add('open');
  document.body.style.overflow='hidden';
}

function switchImg(src, el){
  var mainEl=document.getElementById('mainImgEl');
  if(mainEl) mainEl.src=src;
  document.querySelectorAll('.modal-thumb').forEach(function(t){t.classList.remove('act');});
  if(el) el.classList.add('act');
}

function closeModal(){
  document.getElementById('prodModal').classList.remove('open');
  document.body.style.overflow='';
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')closeModal();});

function goCat(id){
  var cat=C().find(function(c){return c.id===id;}); if(!cat) return;
  var prods=P().filter(function(p){return p.active&&p.catId===id;});
  document.getElementById('main').innerHTML=
    '<div class="pg-hd"><div class="pg-bc"><a onclick="goHome()">Ana Sayfa</a> / '+cat.name+'</div><h1>'+cat.name+'</h1></div>'+
    '<div class="sec"><div class="pg" id="prodGrid"></div></div>';
  renderGrid(prods);
  setNav('nav-'+id);
}

function goPage(pg){
  var s=S();
  if(pg==='bank'){
    document.getElementById('main').innerHTML=
      '<div class="pg-hd"><div class="pg-bc"><a onclick="goHome()">Ana Sayfa</a> / Banka Hesaplarımız</div><h1>Banka Hesaplarımız</h1></div>'+
      '<div class="pg-body"><p style="color:var(--m);margin-bottom:28px;font-size:14px;line-height:1.85;">Havale ve EFT ödemelerinde açıklama kısmına adınızı ve sipariş detayınızı yazmayı unutmayınız.</p>'+
      '<div class="bank"><strong>BANKA HESABI</strong>'+
      '<p>'+s.ibanName+'</p>'+
      '<div class="iban-val">'+s.iban+'</div>'+
      '</div></div>';
  } else if(pg==='contact'){
    document.getElementById('main').innerHTML=
      '<div class="pg-hd"><div class="pg-bc"><a onclick="goHome()">Ana Sayfa</a> / İletişim</div><h1>İletişim</h1></div>'+
      '<div class="pg-body">'+
      '<div class="bank"><strong>TELEFON</strong><p>'+s.phone+'</p></div>'+
      '<div class="bank"><strong>E-POSTA</strong><p>'+s.email+'</p></div>'+
      '<div class="bank"><strong>ADRES</strong><p>'+s.address+'</p></div>'+
      '<div style="margin-top:16px;display:flex;gap:12px;">'+
      (s.whatsapp?'<a href="https://wa.me/'+s.whatsapp+'" target="_blank" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:12px 20px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;border-radius:4px;">'+waIcon()+' WhatsApp</a>':'')+
      '</div></div>';
  } else if(pg==='checkout'){
    if(CART.length===0){
      alert("Sepetiniz boş!");
      goHome();
      return;
    }
    
    var summaryItemsHtml = CART.map(function(item) {
      var p = P().find(function(x){return x.id===item.id;});
      if(!p) return '';
      var itemTotal = p.price * item.qty;
      return '<div class="co-summary-item">' +
        '<span>' + esc(p.name) + ' <strong style="color:var(--gold)">x' + item.qty + '</strong></span>' +
        '<span style="font-weight:600; color:var(--w);">' + itemTotal.toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺</span>' +
      '</div>';
    }).join('');
    
    var cartTotal = getCartTotal();

    document.getElementById('main').innerHTML = 
      '<div class="pg-hd"><div class="pg-bc"><a onclick="goHome()">Ana Sayfa</a> / Havale İle Sipariş</div><h1>Siparişi Tamamla</h1></div>' +
      '<div class="pg-body">' +
        '<div class="checkout-layout">' +
          '<!-- Sol Kolon: Form -->' +
          '<div class="co-form-panel">' +
            '<h2 class="co-box-title">Teslimat ve İletişim Bilgileri</h2>' +
            '<form id="coForm" onsubmit="submitOrder(event)">' +
              '<div class="co-form-group">' +
                '<label for="coName">Ad Soyad *</label>' +
                '<input type="text" id="coName" class="co-input" required placeholder="Adınız ve Soyadınız">' +
              '</div>' +
              '<div class="co-form-group">' +
                '<label for="coPhone">Telefon Numarası *</label>' +
                '<input type="tel" id="coPhone" class="co-input" required placeholder="05__ ___ ____">' +
              '</div>' +
              '<div class="co-form-group">' +
                '<label for="coAddress">Teslimat Adresi *</label>' +
                '<textarea id="coAddress" class="co-input" required rows="4" placeholder="Siparişinizin gönderileceği tam adres" style="resize:vertical; font-family:inherit;"></textarea>' +
              '</div>' +
              '<div class="co-form-group">' +
                '<label for="coNote">Sipariş Notu (Opsiyonel)</label>' +
                '<textarea id="coNote" class="co-input" rows="2" placeholder="Belirtmek istediğiniz özel bir not" style="resize:vertical; font-family:inherit;"></textarea>' +
              '</div>' +
              '<button type="submit" class="checkout-btn" style="margin-top:10px;">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="display:inline-block;vertical-align:middle;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' +
                'Havale/EFT İle Siparişi Onayla' +
              '</button>' +
            '</form>' +
          '</div>' +
          
          '<!-- Sağ Kolon: Özet ve Banka Bilgileri -->' +
          '<div class="co-sidebar">' +
            '<div class="co-box">' +
              '<h2 class="co-box-title">Sipariş Özeti</h2>' +
              '<div style="max-height:220px; overflow-y:auto; margin-bottom:10px; padding-right:6px;">' +
                summaryItemsHtml +
              '</div>' +
              '<div class="co-summary-total">' +
                '<span>Ödenecek Toplam:</span>' +
                '<span class="co-summary-total-price">' + cartTotal.toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' ₺</span>' +
              '</div>' +
            '</div>' +
            
            '<div class="co-box" style="border-color:rgba(232,88,10,0.18);">' +
              '<h2 class="co-box-title">Havale/EFT Banka Hesap Bilgimiz</h2>' +
              '<p style="color:var(--m); font-size:12.5px; line-height:1.6; margin-bottom:14px;">' +
                'Siparişinizi tamamladıktan sonra lütfen aşağıdaki IBAN adresine sipariş tutarını havale ediniz. İşlemlerinizin hızlanması için dekontunuzu göndermeyi unutmayınız.' +
              '</p>' +
              '<div class="co-iban-box" onclick="copyIban()">' +
                '<strong style="display:block; font-size:11px; color:var(--gold); letter-spacing:0.8px; text-transform:uppercase;">BANKA ALICI ADI</strong>' +
                '<p style="font-size:13.5px; color:var(--t); font-weight:600; margin-top:3px;">' + esc(s.ibanName) + '</p>' +
                '<div class="co-iban-val">' +
                  '<span>' + esc(s.iban) + '</span>' +
                  '<span>Kopyala</span>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    setNav('');
  }
}

function submitOrder(e) {
  e.preventDefault();
  var name = document.getElementById('coName').value.trim();
  var phone = document.getElementById('coPhone').value.trim();
  var address = document.getElementById('coAddress').value.trim();
  var note = document.getElementById('coNote').value.trim();
  
  if (!name || !phone || !address) {
    alert("Lütfen gerekli alanları doldurunuz.");
    return;
  }
  
  var s = S();
  var wa = s.whatsapp.replace(/\D/g,'');
  
  var itemsText = CART.map(function(item) {
    var p = P().find(function(x){return x.id===item.id;});
    if(!p) return '';
    var itemTotal = p.price * item.qty;
    return '• ' + item.qty + ' ' + p.unit + ' x ' + p.name + ' (' + p.code + ') - ' + itemTotal.toFixed(2) + ' ₺';
  }).filter(Boolean).join('\n');
  
  var total = getCartTotal().toFixed(2);
  
  var msg = '🛍️ *YENİ SİPARİŞ (Havale/EFT)*\n';
  msg += '---------------------------------\n';
  msg += '👤 *Müşteri:* ' + name + '\n';
  msg += '📞 *Telefon:* ' + phone + '\n';
  msg += '📍 *Adres:* ' + address + '\n';
  if(note) {
    msg += '📝 *Not:* ' + note + '\n';
  }
  msg += '---------------------------------\n';
  msg += '📦 *Ürünler:*\n' + itemsText + '\n';
  msg += '---------------------------------\n';
  msg += '💵 *Toplam Tutar:* ' + total + ' ₺\n';
  msg += '🏦 *Havale Yapılacak Hesap:* ' + s.ibanName + '\n';
  msg += 'IBAN: ' + s.iban + '\n';
  msg += '---------------------------------\n';
  msg += 'Lütfen havaleyi gerçekleştirdikten sonra bu mesajı göndererek onay alınız.';
  
  var encodedMsg = encodeURIComponent(msg);
  var waUrl = 'https://wa.me/' + wa + '?text=' + encodedMsg;
  
  // Clear cart
  CART = [];
  saveCart();
  updateCartUI();
  
  // Show success state
  showSuccessPage(name, total, waUrl);
}

function showSuccessPage(name, totalVal, waUrl) {
  var s = S();
  document.getElementById('main').innerHTML = 
    '<div style="max-width:620px; margin:60px auto; padding:48px 32px; background:var(--d3); border:1px solid rgba(232,88,10,0.18); border-radius:12px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.5);">' +
      '<div style="width:72px; height:72px; background:rgba(34,197,94,0.1); color:#22c55e; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px; border:2px solid #22c55e;">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="36" height="36"><polyline points="20 6 9 17 4 12"/></svg>' +
      '</div>' +
      '<h1 style="font-family:\'Cormorant Garamond\',serif; font-size:32px; color:var(--w); margin-bottom:12px; font-weight:600;">Siparişiniz Alındı!</h1>' +
      '<p style="color:var(--t); font-size:14px; line-height:1.7; margin-bottom:28px;">' +
        'Sayın <strong>' + esc(name) + '</strong>, sipariş talebiniz başarıyla oluşturulmuştur. Ödemenizi tamamlamak için yapmanız gereken detaylar aşağıdadır.' +
      '</p>' +
      
      '<div style="background:var(--d2); border:1px solid rgba(255,255,255,0.03); padding:20px; border-radius:8px; text-align:left; margin-bottom:28px;">' +
        '<strong style="display:block; font-size:10.5px; color:var(--gold); letter-spacing:1px; text-transform:uppercase; margin-bottom:8px;">ÖDEME TUTARI & HESAP BİGSİ</strong>' +
        '<div style="display:flex; justify-content:space-between; margin-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.03); padding-bottom:10px;">' +
          '<span style="color:var(--m); font-size:13px;">Toplam Tutar:</span>' +
          '<strong style="color:var(--gold); font-size:18px;">' + parseFloat(totalVal).toLocaleString('tr-TR', {minimumFractionDigits:2}) + ' ₺</strong>' +
        '</div>' +
        '<p style="font-size:13.5px; color:var(--t); font-weight:600; margin-bottom:6px;">' + esc(s.ibanName) + '</p>' +
        '<div style="font-size:14px; color:var(--w); font-family:monospace; background:var(--d4); padding:10px 12px; border-radius:4px; display:flex; justify-content:space-between; align-items:center;">' +
          '<span>' + esc(s.iban) + '</span>' +
          '<button onclick="navigator.clipboard.writeText(\''+s.iban+'\');alert(\'IBAN Kopyalandı!\');" style="background:none; border:none; color:var(--gold); cursor:pointer; font-size:11px; font-weight:700; text-transform:uppercase; outline:none;">Kopyala</button>' +
        '</div>' +
      '</div>' +
      
      '<p style="color:var(--m); font-size:13px; line-height:1.6; margin-bottom:28px;">' +
        'Sipariş işlemlerinizi başlatabilmemiz için lütfen aşağıdaki butona basarak **WhatsApp üzerinden siparişinizi onaylatın** ve havale dekontunuzu bize gönderin.' +
      '</p>' +
      
      '<a href="' + waUrl + '" target="_blank" style="background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; padding:16px 32px; font-size:14px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:10px; border-radius:4px; box-shadow:0 8px 20px rgba(22,163,74,0.35); transition:all 0.3s; width:100%; justify-content:center;">' +
        waIcon() + ' Siparişi WhatsApp İle Onayla & Dekont Gönder' +
      '</a>' +
      
      '<div style="margin-top:28px;">' +
        '<a onclick="goHome()" style="color:var(--m); font-size:13px; cursor:pointer; text-decoration:underline;">Alışverişe Devam Et</a>' +
      '</div>' +
    '</div>';
}

function liveSearch(q){
  var d=document.getElementById('srchDrop');
  if(!q||q.length<2){d.classList.remove('open');return;}
  var res=P().filter(function(p){return p.active&&(p.name.toLowerCase().includes(q.toLowerCase())||p.code.toLowerCase().includes(q.toLowerCase()));}).slice(0,6);
  if(!res.length){d.classList.remove('open');return;}
  d.innerHTML=res.map(function(p){
    return '<div class="srch-item" onclick="openModal('+p.id+');closeSrch()">'+
      (p.img&&p.img.length>5?'<img src="'+p.img+'" alt="">':'')+
      '<div><div class="si-name">'+esc(p.name)+'</div><div class="si-price">'+p.price.toLocaleString('tr-TR', {minimumFractionDigits:2})+' ₺</div></div></div>';
  }).join('');
  d.classList.add('open');
}
function closeSrch(){document.getElementById('srchDrop').classList.remove('open');}
function doSearch(){
  var q=document.getElementById('srchInp').value.trim();
  if(!q) return;
  var res=P().filter(function(p){return p.active&&(p.name.toLowerCase().includes(q.toLowerCase())||p.code.toLowerCase().includes(q.toLowerCase()));});
  document.getElementById('main').innerHTML=
    '<div class="sec"><div class="sec-hd"><h2>"'+esc(q)+'" için '+res.length+' sonuç</h2></div><div class="pg" id="prodGrid"></div></div>';
  renderGrid(res);
  closeSrch();
}

function buildFooter(){
  var s=S(), cats=C();
  document.getElementById('ftEl').innerHTML=
    '<div class="ft-grid">'+
    '<div><div class="ft-brand">'+s.brandName+'</div><div class="ft-desc">'+s.about+'</div>'+
    '<div class="ft-soc">'+
    (s.instagram?'<a href="'+s.instagram+'" target="_blank">📸</a>':'')+
    (s.facebook?'<a href="'+s.facebook+'" target="_blank">👍</a>':'')+
    (s.whatsapp?'<a href="https://wa.me/'+s.whatsapp+'" target="_blank">💬</a>':'')+
    '</div></div>'+
    '<div class="ft-col"><h4>Kategoriler</h4>'+cats.map(function(c){return '<a onclick="goCat('+c.id+')">'+c.name+'</a>';}).join('')+'</div>'+
    '<div class="ft-col"><h4>İletişim</h4>'+
    (s.phone?'<a href="tel:'+s.phone+'">📞 '+s.phone+'</a>':'')+
    (s.email?'<a href="mailto:'+s.email+'">✉️ '+s.email+'</a>':'')+
    (s.address?'<p>📍 '+s.address+'</p>':'')+
    '</div></div>'+
    '<div class="ft-bot"><span>© '+new Date().getFullYear()+' '+s.brandName+' — Tüm hakları saklıdır.</span></div>';
}

function setNav(id){
  document.querySelectorAll('.nav-in a').forEach(function(a){a.classList.remove('act');});
  var el=document.getElementById(id);
  if(el) el.classList.add('act');
}

function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function waIcon(){return '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413"/></svg>';}

// RUN INITIALIZATION
loadCart();
buildNav();
goHome();
buildFooter();
updateCartUI();
</script>
</body>
</html>