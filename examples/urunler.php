<?php

include '../src/Propars/ProparsApiClient.php';

$email = '****';
$parola = '****';

$api = ProparsApiRoot::connect($email, $parola);



// BILGI: Urun objesinin alan tipleri ve endpoint filtreleme ve siralama alanlari icin asagidaki adresi inceleyebilirsiniz:
// https://api.propars.net/api/v1/docs/#!/core/Item_list

$urun_yonetici = $api->core->item;
$urun_resim_yonetici = $api->core->itemimage;
$marka_yonetici = $api->core->brand;
$kategori_yonetici = $api->core->category;
$doviz_yonetici = $api->core->currency;



/*
 object_set metodu urun objeleri ile ilgili her turlu filtreleme/siralama/sayfalama/guncelleme yapabilecegimiz
 bir ProparsObjectSet objesi dondurur.
 Bu objedeki 'filter', 'limit', 'offset', 'order_by' ve 'page' metotlari ayni siniftan yeni bir obje dondururler
 ve zincir seklinde kullanilabilierler. Istisnai olarak, 'page' metodu 'offset' ile birlikte kullanildiginda son
 cagrilan digerini ezecektir.
 Ornegin asagidaki kullanim; miktari 4 olan urunleri fiyata gore siralayip
 50. siradan sonraki 45 urunun listesini dondurur:
 $urun_yonetici->object_set()->filter(array('amount'=>4)).order_by('price').limit(45).offset(50)->results()
*/

$urun_sorgu = $urun_yonetici->object_set();    // veya $urun_yonetici->all();


function urun_listeleme_ornekleri(){
    global $urun_sorgu;

    // Bir sayfadaki urun sayisinin varsayilan degeri 30'dur
    $urunler = $urun_sorgu->results();
    foreach ($urunler as $urun){
        var_dump($urun);
    }
    
    // Toplam urun sayisi:
    $toplam_urun_sayisi = $urun_sorgu->count();
    var_dump($toplam_urun_sayisi);

    // Tum urunleri sayfalar halinde cekme (20'ser sayfalar halinde):
    foreach ($urun_sorgu->iterate_pages($page_size=20) as $urun_sayfa){
        foreach ($urun_sayfa as $urun){
            var_dump($urun);
        }
    }


    // Ilk 4 sayfayi sayfalar halinde cekme (20'ser sayfalar halinde):
    for($i=1; $i<=4; $i++){
        $urun_sayfa = $urun_sorgu->page($page_size=20);
        foreach ($urun_sayfa as $urun){
            var_dump($urun);
        }
    }

    // Tum urunlerin ilk 15 adedini almak icin;
    $sayi = 15;
    $urunler_ilk_15 = $urun_sorgu->limit($sayi)->results();


    // Tum urunleri 20'ser adet sayfalara bolup 7. sayfayi getirmek icin;
    $sayi = 20;
    $sayfa = 7;
    $urun_sayfa = $urun_sorgu->limit($sayi)->page($sayfa)->results();


    // Tum urunleri 20'ser adet sayfalara bolup 7. sayfayi getirmek;
    // ID'ye gore sirali sekilde getirmek icin;
    $sayi = 20;
    $sayfa = 7;
    $urun_sayfa = $urun_sorgu->limit($sayi)->page($sayfa)->order_by('id')->results();
    $urun_sayfa_fiyata_gore_azalan = $urun_sorgu->limit($sayi)->page($sayfa)->order_by('-price')->results();

    
    // Stogu 4 olup fiyati 10 ve uzeri urunleri filtreleme (ilk sayfa):
    $urun_sayfa = $urun_sorgu->filter(array('amount'=>4, 'min_price'=>10))->results();

    // Stogu 4 olup fiyati 10 ve uzeri urunleri filtreleme (tum urunler, 50'ser sayfalar halinde):
    foreach ($urun_sorgu->filter(array('amount'=>4, 'min_price'=>10))->iterate_pages($page_size=50) as $urun_sayfa){
        print_r($urun_sayfa);
    }


    // Urunlerde belli bir terime gore arama:
    $urun_sayfa = $urun_sorgu->filter(array('search'=>'aranacak terim'))->results();


}


function urun_temel_data_listeleme(){
    global $api;

    // urun temel bilgilerini listeleme:
    // 'id', 'product_code', 'product_name', 'invoice_name', 'price', 'amount', 'tax_rate', 'tax_included', 'currency'
    $urun_liste = $api->core->item->less->object_set()->results();


    // urun sadelestirilmis bilgilerini listeleme:
    // 'id', 'product_code', 'product_name', 'invoice_name', 'price', 'amount', 'tax_rate', 'tax_included', 'currency'
    // alanlarina ek olarak;
    // 'category_detail' ve 'brand_detail' doner.
    $urun_liste = $api->core->item->less->object_set()->results();
}


function tekil_urun_getirme_ornekleri(){
    global $urun_sorgu;

    // ID'si bilinen urunu getirme:
    $urun = $urun_sorgu->get($pk=1000);

    // Belli bir kosula gore belli bir siradaki urunu getirme:
    $sira = 54;
    $urun = $urun_sorgu->filter(array('amount'=>4, 'min_price'=>10))->order_by('amount')->getByIndex($sira);
}


function marka_bul($adi){
    global $marka_yonetici;
    $marka = $marka_yonetici->filter(array('search'=>$adi))->results()[0];
    return $marka;
}

function marka_olustur(){
    global $marka_yonetici;
    $marka = $marka_yonetici->create(array('name'=> 'Test', 'brand_code'=>'test'));
    var_dump($marka);
}


function kategori_bul($adi){
    global $kategori_yonetici;
    $kategori = $kategori_yonetici->filter(array('search'=>$adi))->results()[0];
    return $kategori;
}

function dovizler(){
    global $doviz_yonetici;
    $dovizler = $doviz_yonetici->object_set()->results();
    print_r($dovizler);
}

function urun_olusturma(){
    global $urun_yonetici;

    $marka = marka_bul('test');       // lokal db'de saklanabilir
    $kategori = kategori_bul('test');       // lokal db'de saklanabilir

    var_dump($marka);

    $data = array(
        'product_name'=> 'Test ürünü',
        'product_code'=> 'test1234',
        'category'=> $kategori['id'],
        'brand'=> $marka['id'],
        'amount'=> 20,
        'price'=>100,
        'currency'=>1,      // TL id'si. Tamami icin 'dovizler' fonksiyonuna bakiniz.
        'tax_rate'=>18,
        'tax_included'=>false
    );

    $urun = $urun_yonetici->create($data);
    var_dump($urun, $urun['id']);
}


function urun_guncelleme(){
    global $urun_yonetici;
    $urun_id = 1720399;

    // olusturulurken verilen tum bilgiler guncellenebilir.
    $data = array(
        'amount'=> 63,
        'price'=> 125,
    );

    $guncel = $urun_yonetici->update($pk=$urun_id, $data);
    var_dump($guncel);
}


function urun_fotograf_ekleme(){
    global $urun_resim_yonetici;
    $data = array(
        'item'=>1720399,        // urun id
        'remote_image' => 'http://example.com/image.jpg',
    );
    $foto = $urun_resim_yonetici->create($data);
    var_dump($foto);
}


function urun_fotograf_silme(){
    global $urun_resim_yonetici;
    $pk = 45435274;
    $cevap = $urun_resim_yonetici->delete($pk);
    var_dump($cevap);
}





//urun_olusturma();
//urun_guncelleme();
//urun_fotograf_ekleme();
//urun_fotograf_silme();



?>