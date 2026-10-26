# JT SEO Control Center 1.0.69 düzeltme paketi

Temel: Kullanıcının yüklediği, SHA-256 değeri resmi 1.0.68 güncelleme XML'i ile eşleşen paket.

Düzeltmeler:
- Joomla 6 form olayı için getForm(); genel olaylarda subject/form/sayısal argüman uyumluluğu.
- Eklentinin kendi form XML dosyasının açık dosya yolundan yüklenmesi.
- Kontrol paneli ve canlı SEO puanının JavaScript/CSS dosyaları için doğru Joomla asset yolları.
- Yeni kurulumda iki eklentinin etkinleştirilmesi; güncellemede yönetici tercihinin korunması.
- Paket, alt eklentiler, asset, sağlık kontrolü ve SQL sürüm işaretinin 1.0.69'a eşitlenmesi.

Doğrulama: Joomla 6.2.0, PHP 8.4.27, MariaDB 11.4.
- Sıfırdan kurulum ve 1.0.68 üzerinden güncelleme: başarılı.
- Güncellemede devre dışı bırakılmış eklenti tercihi: korunuyor.
- Kurulum/form uyumluluğu: 68 kontrol başarılı.
- Paketteki 25 PHP dosyası: sözdizimi kontrolü başarılı.
- Chromium: JT SEO sekmesi, çevrilmiş etiketler, canlı puan güncellemesi ve makale kaydı başarılı.
- Başlık, açıklama, görsel, canonical ve robots ayarları kaydediliyor ve ön yüzde kullanılıyor.
- Open Graph ve JSON-LD çıktısı mevcut; kontrol paneli ve sitemap HTTP kontrolleri başarılı.
- index/noindex sitemap dahil etme/dışlama ve kayıtta fiziksel sitemap yenileme: 6 kontrol başarılı.
- Ana ZIP ve üç alt ZIP bütünlüğü kontrol edildi; yeniden üretimde SHA-256 değişmiyor.

Bu kontroller belirtilen akışları doğrular; mümkün olan tüm Joomla şablon ve üçüncü taraf eklenti birleşimleri test edilmedi.
Kurulum: Joomla yönetiminde Sistem > Kurulum > Eklentiler üzerinden ZIP'i yükleyin.
1.0.68'i kaldırmak gerekmez; paket güncelleme olarak yüklenir.
Resmi sürüm: https://github.com/joomtheme/JT-SEO-Control-Center/releases/tag/v1.0.69
Yayımlanan ZIP'in SHA-256 değeri güncelleme XML'i ve SHA256SUMS.txt ile eşleşiyor.

Kaynaklar depoya eklendikten sonra 6 paket/sürüm doğrulama testi geçti.
Bileşen ve eklenti kaynakları yayımlanan ZIP içindeki çalışma dosyalarıyla birebir eşleşiyor.
Kaynak derlemesi güncel paket README'sini içerdiği için arşiv hash'i yayımlanan ZIP'ten farklı olabilir.
