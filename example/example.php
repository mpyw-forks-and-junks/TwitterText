<?php

use mpyw\TwitterText\Linkifier;
use mpyw\TwitterText\ImageUtil4JP;

// If you are using composer autoloader, this registeration is not needed.
spl_autoload_register(function ($class) {
    if (substr($class, 0, 17) === 'mpyw\\TwitterText\\') {
        require __DIR__ . '/../src/' . substr($class, 17) . '.php';
    }
});

// Configure your credentials
$consumer_key       = '';
$consumer_secret    = '';
$oauth_token        = '';
$oauth_token_secret = '';
$timezone           = 'Asia/Tokyo';

// Create linkifier
$lf = new Linkifier(array(
    'mention' => function (\stdClass $mention) {
        return sprintf(
            '<a href="https://twitter.com/%1$s">@%1$s</a>',
            $mention->screen_name
        );
    },
    'url' => function (\stdClass $url) {
        if (!isset($url->expanded_url, $url->display_url)) {
            $url->expanded_url = $url->url;
            $url->display_url = mb_strimwidth($url->url, 0, 25, '...');
        }
        $img = ImageUtil4JP::getUrls($url->expanded_url);
        return $img ?
            sprintf('<div><a href="%s">%s<br><img src="%s" alt=""></a></div>',
                $url->expanded_url,
                $url->display_url,
                $img->small
            ) :
            sprintf('<a href="%s">%s</a>',
                $url->expanded_url,
                $url->display_url
            )
        ;
    },
    'hashtag' => function (\stdClass $hashtag) {
        return sprintf('<a href="http://twitter.com/search/%s">%s</a>',
            urlencode('#' . $hashtag->text),
            '#' . $hashtag->text
        );
    },
    'symbol' => function (\stdClass $symbol) {
        return sprintf('<a href="http://twitter.com/search/%s">%s</a>',
            urlencode('$' . $symbol->text),
            '$' . $symbol->text
        );
    },
    'photos' => function (array $photos) {
        $str = sprintf(
            '<a href="%s">%s</a><br>',
            $photos[0]->expanded_url,
            $photos[0]->display_url
        );
        foreach ($photos as $photo) {
            $str .= sprintf('<a href="%s"><img src="%s" alt=""></a>',
                $photo->expanded_url,
                $photo->media_url . ':thumb'
            );
        }
        return '<div>' . $str . '</div>';
    },
    'video' => function (\stdClass $video) {
        return sprintf(
            '<a href="%s">%s</a><br><video src="%s" controls></video>',
            $video->expanded_url,
            $video->display_url,
            $video->video_info->variants[3]->url
        );
    }
));


try {

    // Download TwistOAuth.phar into the temporary directory
    $path = sys_get_temp_dir() . '/TwistOAuth.phar';
    if (!is_readable($path)) {
        switch (true) {
            case !$local  = @fopen($path, 'wb'):
            case !$remote = @fopen('https://raw.githubusercontent.com/mpyw/TwistOAuth/master/build/TwistOAuth.phar', 'rb'):
            case !@stream_copy_to_stream($remote, $local):
                $error = error_get_last();
                throw new \RuntimeException($error['message']);
        }
    }
    require $path;

    // Create TwistOAuth
    $to = new \TwistOAuth(
        $consumer_key,
        $consumer_secret,
        $oauth_token,
        $oauth_token_secret
    );

    // Create DateTimeZone
    $tz = new \DateTimeZone($timezone);

    // Get home timeline
    $statuses = $to->get('statuses/home_timeline', array('count' => 200));

} catch (\Exception $e) {

    header('Content-Type: text/plain; charset=UTF-8', true, 500);
    echo $e->getMessage() . "\n";

}

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test</title>
</head>
<body>
<?php foreach ($statuses as $i => $status): ?>
<?php if (isset($status->retweeted_status)) { $status = $status->retweeted_status; } ?>
<?php if ($i):?>
    <hr>
<?php endif; ?>
    <article>
        <p style="font-size:120%;">
            <img src="<?php echo $status->user->profile_image_url?>" alt="<?php echo $status->user->screen_name?>">
            @<?php echo $status->user->screen_name?>(<?php echo htmlspecialchars($status->user->name, ENT_QUOTES, 'UTF-8')?>)
        </p>
        <p>
            <pre><?php echo $lf->linkifyStatus($status)?></pre>
        </p>
        <p style="font-size:75%;"><?php
            $date = new \DateTime($status->created_at, $tz);
            echo $date->format('Y-m-d H:i:s');
        ?></p>
    </article>
<?php endforeach; ?>
</body>
</html>
