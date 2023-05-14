# Как использовать

1. Добавить подгрузку классов из папки Sprint,
   Например, через `composer.json`:
   ```
   {
     "autoload": {
       "psr-4": {
         "Sprint\\": "lib/Sprint/"
       }
     }
   }
   ```
2. Добавить [билдер в конфиг миграций](https://github.com/andreyryabin/sprint.migration/wiki/%D0%9A%D0%B0%D1%81%D1%82%D0%BE%D0%BC%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F-%D0%BC%D0%B8%D0%B3%D1%80%D0%B0%D1%86%D0%B8%D0%B9#%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D1%80-%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D1%8F-%D0%BA%D0%BE%D0%BD%D1%81%D1%82%D1%80%D1%83%D0%BA%D1%82%D0%BE%D1%80%D0%B0-%D0%BC%D0%B8%D0%B3%D1%80%D0%B0%D1%86%D0%B8%D0%B8), см. пример в `migrations.cfg.php`
3. Использовать через «Создать миграцию для свойств заказа»