# Bulut-Bilişim-Proje
Bulut Bilişim Proje Ödevi Raporu
	
| Hazırlayanlar       | Numaralar   |
| :-----------------: | ----------- |
| Deniz Erdem ARAS    | B221200014  |
| Ahmet Timuçin UÇAN  | B221200059  |
| Metehan YILDIZ      | B231200377  |

Sunum videosu: https://www.youtube.com/watch?v=uhfzDZ4CvI4 <br>
(18 Ocak'a kadar) Çalışan proje: http://13.61.42.129


## UYGULAMA ADIMLARI
Projenin bulut ortamında devreye alınması sürecinde izlenen teknik adımlar aşağıda kronolojik sırayla listelenmiştir.

### Adım 1: AWS Bulut Altyapısının Hazırlanması
Projenin barındırılacağı sanal sunucu ortamı Amazon Web Services üzerinde aşağıdaki konfigürasyonlarla oluşturulmuştur:
1.	EC2 Örneklemesi:
- AMI: Ubuntu Server 24.04 LTS (HVM), SSD Volume Type seçildi.
- Instance Type: t3.micro (Free Tier uyumlu) tercih edildi.
- Key Pair: Sunucuya SSH bağlantısı yapabilmek için .pem uzantılı bir anahtar çifti oluşturuldu ve indirildi.
2.	Ağ ve Güvenlik Duvarı (Security Groups):
- Sunucunun dış dünyayla iletişim kurabilmesi için Inbound Rules şu şekilde yapılandırıldı:
- SSH (Port 22): Yönetimsel erişim için.
- HTTP (Port 80): Web uygulamasının son kullanıcıya sunulması için.
3.	Statik IP Ataması (Elastic IP):
- Sunucu yeniden başlatıldığında IP adresinin değişmesini önlemek ve DNS tanımlarına sabit bir adres sunmak amacıyla AWS panelinden bir Elastic IP tahsis edildi ve oluşturulan EC2 örneği ile ilişkilendirildi.

### Adım 2: Sunucu Ortamının Hazırlanması
SSH protokolü üzerinden sunucuya bağlanılarak gerekli paket güncellemeleri yapılmış ve konteyner motoru kurulmuştur.
1.	Sistem Güncellemesi: 
```
sudo apt-get update && sudo apt-get upgrade -y
```
2.	Docker ve Docker Compose Kurulumu: Uygulamanın konteynerize çalışabilmesi için Docker Engine ve orkestrasyon aracı Docker Compose yüklendi:
```
sudo apt install docker.io -y
sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```
 
### Adım 3: Proje Dizin Yapısı ve Konteyner Konfigürasyonu
Sunucu üzerinde /home/ubuntu/kanban-projesi dizini oluşturularak mikroservis mimarisini tanımlayan dosyalar hazırlandı.
1.	Dizin Yapısı: Kaynak kodların düzenli durması için src klasörü oluşturuldu.
2.	Web Servisi İmaj Tanımı (Dockerfile): PHP uygulamasının veritabanı ile konuşabilmesi için php:8.1-apache resmi imajı baz alınarak özel bir imaj tanımlandı ve docker-php-ext-install pdo pdo_mysql komutu ile gerekli sürücüler eklendi.
3.	Orkestrasyon Dosyası (docker-compose.yml):
- Web Servisi: 80 portuna yönlendirildi ve kaynak kodlar (./src) volume olarak bağlandı.
- Veritabanı Servisi: mysql:5.7 imajı kullanıldı, root şifresi ve veritabanı ismi (tododb) environment değişkeni olarak tanımlandı.
- Ağ: Servislerin izole haberleşmesi için kanban-network tanımlandı.
- Kalıcılık: Veri kaybını önlemek için db_data volume'ü oluşturuldu.

### Adım 4: Uygulama Kodlarının Entegrasyonu
Backend ve Frontend kodları sunucuya yerleştirildi.
1.	Backend (src/api.php): Veritabanı bağlantı dizisinde (DSN) host=localhost yerine Docker servis adı olan host=db kullanılarak konteynerler arası DNS çözümlemesi sağlandı. PHP PDO yapısı ile CRUD işlemleri kodlandı.
2.	Frontend (src/index.html): HTML5 Drag & Drop API kullanılarak sürükle-bırak mantığı ve Fetch API ile backend haberleşmesi sağlandı. Kullanıcı deneyimi için SweetAlert2 kütüphanesi CDN üzerinden projeye dahil edildi.

### Adım 5: Servislerin Başlatılması ve Veritabanı Kurulumu
Tüm yapılandırma tamamlandıktan sonra uygulama canlıya alındı.
1.	Konteynerlerin Başlatılması: Aşağıdaki komut ile imajlar derlendi (build) ve servisler arka planda (detached) çalıştırıldı: 
```
sudo docker-compose up -d --build
```
2.	Veritabanı Şemasının Oluşturulması (Migration): MySQL konteynerine komut satırından erişilerek ilk tablolar (todos ve categories) oluşturuldu ve Türkçe karakter desteği (utf8mb4) ayarlandı:
```
sudo docker-compose exec db mysql -u root -p<password> tododb -e "
CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    text TEXT NOT NULL,
    category VARCHAR(50),
    color VARCHAR(20),
    due_date DATE,
    status VARCHAR(20) DEFAULT 'todo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#4F46E5'
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"
```

