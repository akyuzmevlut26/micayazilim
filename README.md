# **MicaYazılım**

### **Install**

- composer install
- php artisan key:generate
- php artisan migrate

### **For Queue Settings**
- The setting 'QUEUE_CONNECTION' setting value should be 'database' in .env file

### **To Run Trendyol Job**
- php artisan queue:work --timeout=300
- php artisan job:dispatch TrendyolJob
