# playavr-api-v2

## If you liked it you can support my work
[!["Buy Me A Coffee"](https://raw.githubusercontent.com/michal-repo/random_stuff/refs/heads/main/bmac_small.png)](https://buymeacoffee.com/michaldev)


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

To create your own scripts follow instructions in [this repo](https://github.com/michal-repo/web_vr_video_player/blob/main/README.md#generating-your-own-json-file-with-video-sources).

**Alternatively modify `api.php` to process your own video files source.**
