# Installation
1. Download and Composer Install
```
git clone https://github.com/ammarfaizi2/fb_scraper
cd fb_scraper
composer install -vvv
```
2. Prepare your database mysql and import db.sql
3. Edit config.php


# Group posts scraper
```shell
php group.php [group_id]
```

# Profile and Fanspage posts scraper
```shell
php timeline_scraper [user_id or username]
```