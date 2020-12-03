# PHPWebP
Create on-the-fly WebP images from jpg and png

## USAGE: 
1. Upload any jpg,jpeg on png file (file.png) to the webserver. 
2. Interchange the file extension to webp (file.webp) or append .webp extension (file.png.webp) and check the WEBP_EXTENSTION_METHOD below
3. Select the conversion method. CWEBP creates smaller files but relies on the webp package and is not available by default. GD should be supported in most cases
4. Rewrite all non existent webp files to /<PATH TO>/webpgenerator.php using the config below:


## NGINX CONFIG:
```
location ~* \.webp$ {
  try_files $uri /path-to/webpgenerator.php;
}
```

## APACHE CONFIG:
```
<IfModule mod_rewrite.c>
  RewriteEngine on
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_URI} \.(webp)$
  RewriteRule ^/?(.+)\.(webp)$ /path-to/webp-generator.php [QSA,L]
</IfModule>
```
