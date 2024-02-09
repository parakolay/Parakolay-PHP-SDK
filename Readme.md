
# Parakolay PHP SDK

Parakolay PHP SDK, [Parakolay](https://www.parakolay.com) ödeme sistemine entegrasyon sağlayarak, web uygulamalarında kolayca ödeme işlemi yapılmasını ve 3D Secure doğrulama sürecinin yönetilmesini sağlar. Bu SDK, PHP tabanlı projelerde hızlı bir şekilde entegre edilebilir ve kullanılabilir.

## Başlarken

Bu bölüm, Parakolay PHP SDK'nın nasıl kurulacağı ve yapılandırılacağı hakkında adım adım talimatlar içerir.

### Gereksinimler

- PHP 7.2 veya üzeri
- curl PHP eklentisi
- composer
- guzzle v7

### Kurulum

Parakolay PHP SDK, Composer aracılığıyla kolayca kurulabilir. Projenizin kök dizininde aşağıdaki komutu çalıştırarak SDK'yı projenize ekleyin:

```
composer require parakolay/sdk
```

### Yapılandırma

SDK'yı projenize ekledikten sonra, API anahtarlarınızı ve gerekli yapılandırmayı ayarlamanız gerekmektedir. Örnek bir yapılandırma aşağıda verilmiştir:

```php
// API ve Merchant bilgileriniz
$apiKey = 'YOUR_API_KEY';
$apiSecret = 'YOUR_API_SECRET';
$merchantNumber = 'YOUR_MERCHANT_NUMBER';
$conversationId = 'CURRENT_ORDER_ID';

// SDK'nın kullanılacağı ortamın URL'si
$baseUrl = 'https://api.parakolay.com';
$testUrl = 'https://api-test.parakolay.com';

// Parakolay nesnesinin oluşturulması
$apiClient = new Parakolay($baseUrl, $apiKey, $apiSecret, $merchantNumber, $conversationId);
```

## Ödeme İşlemleri

### 3D Secure ile Ödeme Başlatma

3D Secure ile ödeme işlemini başlatmak için aşağıdaki metod kullanılır:

```php
$result = $apiClient->init3DS("KART_NUMARASI", "KART_SAHİBİ_AD_SOYAD", "SON_KULLANMA_AY (MM)", "SON_KULLANMA_YIL (YY)", "CVV", (int) MIKTAR, (int) PUAN_MIKTARI, "CALLBACK_URL");
```

### 3D Secure ile Ödeme Tamamlama

Kullanıcı 3D Secure doğrulamasını tamamladıktan sonra, ödeme işlemini tamamlamak için aşağıdaki metod kullanılır:

```php
$result = $apiClient->complete3DS();
```

## Lisans

Bu proje [MIT Lisansı](LICENSE) altında lisanslanmıştır.
