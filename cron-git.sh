cd /home/xs679489/kyogokupro.com/public_html
/usr/bin/git commit -a -m "auto-upload"
/usr/bin/git push origin main

/usr/bin/php7.3 webp.php ./html 0 true
/usr/bin/php7.3 webp.php ./images 0 true