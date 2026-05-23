<?php
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function checkAuth() {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    if ($user !== 'admin' || $pass !== 'Dekopanel2025') {
        http_response_code(401);
        die(json_encode(['ok'=>false,'msg'=>'Kullanıcı adı veya şifre hatalı']));
    }
}

function saveImage($fileKey, $prefix) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
    $tmp  = $_FILES[$fileKey]['tmp_name'];
    if (!@getimagesize($tmp)) return null;
    $filename = $prefix . '_' . time() . '_' . rand(1000,9999) . '.jpg';
    $dest = UPLOAD_DIR . $filename;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    return move_uploaded_file($tmp, $dest) ? $filename : null;
}

switch ($action) {

case 'get_all':
case 'get_categories':
    $db = getDB();
    $cats = $db->query("SELECT * FROM categories WHERE active=1 ORDER BY sort_order")->fetchAll();
    if ($action === 'get_categories') { echo json_encode(['ok'=>true,'categories'=>$cats]); break; }
    $prods = $db->query("SELECT p.*, (SELECT filename FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as main_img FROM products p WHERE p.active=1 ORDER BY p.category_id, p.sort_order")->fetchAll();
    foreach ($prods as &$p) {
        $p['main_image'] = $p['main_img'] ? UPLOAD_URL.$p['main_img'] : null;
        $ei = $db->prepare("SELECT filename FROM product_images WHERE product_id=? AND is_primary=0 ORDER BY sort_order");
        $ei->execute([$p['id']]);
        $p['extra_images'] = array_map(fn($r)=>UPLOAD_URL.$r['filename'], $ei->fetchAll());
    }
    $banners = $db->query("SELECT * FROM banners WHERE active=1 ORDER BY sort_order")->fetchAll();
    foreach ($banners as &$b) { $b['url'] = $b['filename'] ? UPLOAD_URL.$b['filename'] : null; }
    $settings = [];
    foreach ($db->query("SELECT key_name, value FROM settings")->fetchAll() as $r) { $settings[$r['key_name']] = $r['value']; }
    echo json_encode(['ok'=>true,'categories'=>$cats,'products'=>$prods,'banners'=>$banners,'settings'=>$settings]);
    break;

case 'get_products':
    checkAuth();
    $db = getDB();
    $prods = $db->query("SELECT p.*, c.name as cat_name, (SELECT filename FROM product_images WHERE product_id=p.id AND is_primary=1 LIMIT 1) as main_img FROM products p LEFT JOIN categories c ON c.id=p.category_id GROUP BY p.id ORDER BY p.category_id, p.sort_order")->fetchAll();
    foreach ($prods as &$p) {
        $p['main_image'] = $p['main_img'] ? UPLOAD_URL.$p['main_img'] : null;
        $ei = $db->prepare("SELECT filename FROM product_images WHERE product_id=? AND is_primary=0 ORDER BY sort_order");
        $ei->execute([$p['id']]);
        $p['extra_images'] = array_map(fn($r)=>UPLOAD_URL.$r['filename'], $ei->fetchAll());
    }
    echo json_encode(['ok'=>true,'products'=>$prods]);
    break;

case 'save_product':
    checkAuth();
    $db = getDB();
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['ok'=>false,'msg'=>'Ad gerekli']); break; }
    $d = [$name, trim($_POST['code']??''), trim($_POST['desc']??''), floatval($_POST['price']??0), ($_POST['old_price']??'')?floatval($_POST['old_price']):null, intval($_POST['cat_id']??0), trim($_POST['unit']??'adet'), trim($_POST['badge']??'')?:null, intval($_POST['active']??1)];
    if ($id > 0) { $d[]=$id; $db->prepare("UPDATE products SET name=?,code=?,description=?,price=?,old_price=?,category_id=?,unit=?,badge=?,active=?,updated_at=NOW() WHERE id=?")->execute($d); }
    else { $db->prepare("INSERT INTO products (name,code,description,price,old_price,category_id,unit,badge,active) VALUES (?,?,?,?,?,?,?,?,?)")->execute($d); $id=$db->lastInsertId(); }
    if (!empty($_FILES['main_image']['name'])) {
        $fn = saveImage('main_image','prod_'.$id);
        if ($fn) { $old=$db->prepare("SELECT filename FROM product_images WHERE product_id=? AND is_primary=1"); $old->execute([$id]); if($r=$old->fetch()){@unlink(UPLOAD_DIR.$r['filename']);} $db->prepare("DELETE FROM product_images WHERE product_id=? AND is_primary=1")->execute([$id]); $db->prepare("INSERT INTO product_images (product_id,filename,is_primary,sort_order) VALUES (?,?,1,0)")->execute([$id,$fn]); }
    }
    for ($i=1;$i<=4;$i++) {
        if (!empty($_FILES['image_'.$i]['name'])) {
            $cnt=$db->prepare("SELECT COUNT(*) FROM product_images WHERE product_id=? AND is_primary=0"); $cnt->execute([$id]);
            if (intval($cnt->fetchColumn())>=4) break;
            $fn=saveImage('image_'.$i,'extra_'.$id.'_'.$i);
            if ($fn) $db->prepare("INSERT INTO product_images (product_id,filename,is_primary,sort_order) VALUES (?,?,0,?)")->execute([$id,$fn,$i]);
        }
    }
    echo json_encode(['ok'=>true,'id'=>$id]);
    break;

case 'delete_product':
    checkAuth();
    $db=getDB(); $id=intval($_POST['id']??0);
    $imgs=$db->prepare("SELECT filename FROM product_images WHERE product_id=?"); $imgs->execute([$id]);
    foreach($imgs->fetchAll() as $img){@unlink(UPLOAD_DIR.$img['filename']);}
    $db->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);
    $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]);
    break;

case 'delete_extra_by_filename':
    checkAuth();
    $db=getDB(); $pid=intval($_POST['product_id']??0); $file=basename($_POST['filename']??'');
    $row=$db->prepare("SELECT id FROM product_images WHERE product_id=? AND filename=? AND is_primary=0"); $row->execute([$pid,$file]);
    if ($r=$row->fetch()){@unlink(UPLOAD_DIR.$file);$db->prepare("DELETE FROM product_images WHERE id=?")->execute([$r['id']]);echo json_encode(['ok'=>true]);}
    else echo json_encode(['ok'=>false]);
    break;

case 'save_banner':
    checkAuth();
    $db=getDB(); $id=intval($_POST['id']??0);
    if (!empty($_FILES['banner_image']['name'])) {
        $fn=saveImage('banner_image','banner_'.$id);
        if ($fn){$old=$db->prepare("SELECT filename FROM banners WHERE id=?");$old->execute([$id]);if($r=$old->fetch()&&$r['filename'])@unlink(UPLOAD_DIR.$r['filename']);$db->prepare("UPDATE banners SET filename=? WHERE id=?")->execute([$fn,$id]);echo json_encode(['ok'=>true,'url'=>UPLOAD_URL.$fn]);}
        else echo json_encode(['ok'=>false,'msg'=>'Yüklenemedi']);
    } else echo json_encode(['ok'=>false,'msg'=>'Görsel yok']);
    break;

case 'delete_banner':
    checkAuth();
    $db=getDB(); $id=intval($_POST['id']??0);
    $old=$db->prepare("SELECT filename FROM banners WHERE id=?");$old->execute([$id]);
    if($r=$old->fetch()&&isset($r['filename']))@unlink(UPLOAD_DIR.$r['filename']);
    $db->prepare("UPDATE banners SET filename=NULL WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]);
    break;

case 'save_category':
    checkAuth();
    $db=getDB(); $id=intval($_POST['id']??0); $name=trim($_POST['name']??''); $sort=intval($_POST['sort']??0);
    $slug=strtolower(preg_replace('/[^a-zA-Z0-9]+/','-',$name));
    if(!$name){echo json_encode(['ok'=>false,'msg'=>'Ad gerekli']);break;}
    if($id>0){$db->prepare("UPDATE categories SET name=?,slug=?,sort_order=? WHERE id=?")->execute([$name,$slug,$sort,$id]);}
    else{$db->prepare("INSERT INTO categories (name,slug,sort_order) VALUES (?,?,?)")->execute([$name,$slug,$sort]);$id=$db->lastInsertId();}
    echo json_encode(['ok'=>true,'id'=>$id]);
    break;

case 'get_settings':
    checkAuth();
    $db=getDB();$s=[];
    foreach($db->query("SELECT key_name,value FROM settings")->fetchAll() as $r){$s[$r['key_name']]=$r['value'];}
    echo json_encode(['ok'=>true,'settings'=>$s]);
    break;

case 'save_settings':
    checkAuth();
    $db=getDB();
    foreach(['brand_name','brand_sub','phone','whatsapp','email','address','instagram','facebook','youtube','iban','iban_name','about'] as $f){
        if(isset($_POST[$f]))$db->prepare("INSERT INTO settings (key_name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=?")->execute([$f,$_POST[$f],$_POST[$f]]);
    }
    echo json_encode(['ok'=>true]);
    break;


case 'save_logo':
    checkAuth();
    if (!empty($_FILES['logo']['name'])) {
        $fn = saveImage('logo', 'logo');
        if ($fn) {
            $db = getDB();
            $db->prepare("INSERT INTO settings (key_name,value) VALUES ('logo',?) ON DUPLICATE KEY UPDATE value=?")->execute([$fn,$fn]);
            echo json_encode(['ok'=>true,'url'=>UPLOAD_URL.$fn]);
        } else { echo json_encode(['ok'=>false,'msg'=>'Yüklenemedi']); }
    } else { echo json_encode(['ok'=>false,'msg'=>'Dosya seçilmedi']); }
    break;


case 'delete_category':
    checkAuth();
    $db = getDB();
    $id = intval($_POST['id'] ?? 0);
    $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
    echo json_encode(['ok'=>true]);
    break;

default:
    echo json_encode(['ok'=>false,'msg'=>'Geçersiz']);
}
