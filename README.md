# playavr-api-v2

# Setup

Copy `api` folder in root directory of web server.

Install composer packages.

## Config

Modify values in `config.php`

Implement your own auth if needed.

## Apache

Add following section to your apache site config to enable `.htaccess`.

```
<Directory /var/www/html/api/playa/v2>
        AllowOverride All
</Directory>
```

# Files

Use [following scripts](https://github.com/michal-repo/web_vr_video_player/tree/main/scripts) to generate json file.

To create your own scripts follow instructions for in [this repo](https://github.com/michal-repo/web_vr_video_player/blob/main/README.md#generating-your-own-json-file-with-video-sources).

**Alternatively modify `api.php` to process your own video files source.**
