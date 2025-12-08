# 🏢 Sistem Pontaj Firma

Sistem de pontaj electronic pentru firme cu funcționalități complete de management al orelor de lucru.

## ✨ Funcționalități

- ✅ **Autentificare** angajați și admini
- ✅ **Pontare rapidă** (intrare/ieșire)
- ✅ **Istoric pontaje** cu filtre avansate
- ✅ **Rapoarte** și statistici
- ✅ **Export date** (Excel, CSV, PDF)
- ✅ **Gestiune angajați** și departamente
- ✅ **Dashboard** cu statistici în timp real

## 🚀 Instalare

1. Clonează repository-ul
2. Configurare baza de date:
   - Importă `includes/database.sql`
   - Actualizează `includes/config.php`
3. Accesează `index.php` în browser

## 🔧 Configurare

1. Creează baza de date MySQL
2. Configurează conexiunea în `includes/config.php`:
```php
$host = 'localhost';
$dbname = 'pontaj_firma';
$username = 'root';
$password = '';