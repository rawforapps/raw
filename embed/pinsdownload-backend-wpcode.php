<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Pinterest Downloader
 * WPCode PHP Snippet
 *
 * Insertion: Auto Insert -> Run Everywhere (renders nothing on pages
 * where it isn't placed), or drop the WPCode-assigned shortcode for
 * this snippet (Smart Tags -> "Shortcode" in the snippet's editor,
 * looks like [wpcode id="XXXX"]) into a Shortcode block on the page
 * where you want the tool to appear.
 */


/* =========================================================
   PROCESS PINTEREST REQUEST
========================================================= */

$pdl_result = null;
$pdl_error  = '';

if (
    isset($_SERVER['REQUEST_METHOD']) &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['pdl_action']) &&
    $_POST['pdl_action'] === 'fetch'
) {

    $pdl_url = '';

    if (isset($_POST['pinterest_url'])) {
        $pdl_url = trim(wp_unslash($_POST['pinterest_url']));
    }


    /* -----------------------------------------------------
       BASIC VALIDATION
    ----------------------------------------------------- */

    if ($pdl_url === '') {

        $pdl_error = 'Please enter a Pinterest URL.';

    } elseif (!filter_var($pdl_url, FILTER_VALIDATE_URL)) {

        $pdl_error = 'Please enter a valid Pinterest URL.';

    } else {

        $pdl_host = wp_parse_url($pdl_url, PHP_URL_HOST);

        if (!$pdl_host) {

            $pdl_error = 'Invalid Pinterest URL.';

        } else {

            $pdl_host = strtolower($pdl_host);

            /*
             * Remove www.
             */
            $pdl_host = preg_replace('/^www\./', '', $pdl_host);

            $pdl_allowed_hosts = array(
                'pinterest.com',
                'pin.it'
            );

            if (!in_array($pdl_host, $pdl_allowed_hosts, true)) {

                $pdl_error = 'Please enter a Pinterest URL.';

            } else {

                /* -------------------------------------------------
                   PINTSAVE API
                ------------------------------------------------- */

                $pdl_api_response = wp_remote_post(
                    'https://pintsave.net/api/fetch-media',
                    array(
                        'timeout' => 45,

                        'headers' => array(
                            'Accept' => '*/*',
                            'X-Requested-With' => 'XMLHttpRequest',
                        ),

                        'body' => array(
                            'url' => $pdl_url,
                        ),
                    )
                );


                /* -------------------------------------------------
                   API CONNECTION ERROR
                ------------------------------------------------- */

                if (is_wp_error($pdl_api_response)) {

                    $pdl_error = 'Unable to connect to the media service. Please try again.';

                } else {

                    $pdl_status = wp_remote_retrieve_response_code(
                        $pdl_api_response
                    );

                    $pdl_body = wp_remote_retrieve_body(
                        $pdl_api_response
                    );


                    /* -------------------------------------------------
                       HTTP ERROR
                    ------------------------------------------------- */

                    if ($pdl_status !== 200) {

                        $pdl_error = 'The media service returned an error. Please try again.';

                    } else {

                        $pdl_result = json_decode(
                            $pdl_body,
                            true
                        );


                        /* -------------------------------------------------
                           INVALID JSON / EMPTY RESULT
                        ------------------------------------------------- */

                        if (
                            !is_array($pdl_result) ||
                            empty($pdl_result['media']) ||
                            !is_array($pdl_result['media'])
                        ) {

                            $pdl_error = 'No downloadable media was found for this Pinterest URL.';

                            $pdl_result = null;
                        }
                    }
                }
            }
        }
    }
}


/* =========================================================
   HTML + CSS
========================================================= */

?>

<style>

.pdl-wrapper {
    width: 100%;
    max-width: 850px;
    margin: 30px auto;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

.pdl-wrapper *,
.pdl-wrapper *::before,
.pdl-wrapper *::after {
    box-sizing: border-box;
}

.pdl-box {
    background: #ffffff;
    border: 1px solid #eeeeee;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
}

.pdl-title {
    margin: 0 0 8px;
    text-align: center;
    font-size: 28px;
    line-height: 1.3;
    font-weight: 700;
    color: #222222;
}

.pdl-description {
    margin: 0 0 25px;
    text-align: center;
    color: #777777;
    font-size: 15px;
    line-height: 1.6;
}

.pdl-form {
    display: flex;
    width: 100%;
    gap: 10px;
    margin: 0;
}

.pdl-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 54px;
    padding: 0 16px;
    border: 1px solid #d9d9d9;
    border-radius: 9px;
    background: #ffffff;
    color: #222222;
    font-size: 16px;
    outline: none;
}

.pdl-input:focus {
    border-color: #e60023;
    box-shadow: 0 0 0 3px rgba(230, 0, 35, 0.08);
}

.pdl-button {
    height: 54px;
    padding: 0 28px;
    border: 0;
    border-radius: 9px;
    background: #e60023;
    color: #ffffff;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}

.pdl-button:hover {
    background: #c90020;
}

.pdl-button:disabled {
    opacity: 0.7;
    cursor: wait;
}

.pdl-loading {
    display: none;
    margin-top: 18px;
    text-align: center;
    color: #666666;
    font-size: 14px;
}

.pdl-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    margin-right: 7px;
    vertical-align: middle;
    border: 3px solid #dddddd;
    border-top-color: #e60023;
    border-radius: 50%;
    animation: pdl-spin 0.8s linear infinite;
}

@keyframes pdl-spin {
    to {
        transform: rotate(360deg);
    }
}

.pdl-error {
    margin-top: 20px;
    padding: 14px 16px;
    border: 1px solid #ffd0d0;
    border-radius: 9px;
    background: #fff0f0;
    color: #b00020;
    font-size: 14px;
    line-height: 1.5;
}

.pdl-results {
    margin-top: 28px;
}

.pdl-result {
    margin-bottom: 22px;
    padding: 18px;
    border: 1px solid #eeeeee;
    border-radius: 14px;
    background: #fafafa;
}

.pdl-media {
    display: block;
    width: 100%;
    max-height: 600px;
    margin: 0 auto 18px;
    border-radius: 10px;
    background: #111111;
    object-fit: contain;
}

.pdl-info {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}

.pdl-info-item {
    min-width: 0;
    padding: 11px;
    border: 1px solid #eeeeee;
    border-radius: 8px;
    background: #ffffff;
}

.pdl-info-label {
    display: block;
    margin-bottom: 4px;
    color: #888888;
    font-size: 12px;
    line-height: 1.3;
}

.pdl-info-value {
    display: block;
    color: #222222;
    font-size: 14px;
    line-height: 1.4;
    font-weight: 600;
    word-break: break-word;
}

.pdl-download {
    display: block;
    width: 100%;
    padding: 14px 18px;
    border-radius: 9px;
    background: #e60023;
    color: #ffffff !important;
    text-align: center;
    text-decoration: none !important;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.4;
}

.pdl-download:hover {
    background: #c90020;
    color: #ffffff !important;
}

.pdl-meta {
    margin-top: 20px;
    padding: 15px;
    border-radius: 9px;
    background: #f7f7f7;
    color: #555555;
    font-size: 14px;
    line-height: 1.5;
}

.pdl-meta-title {
    color: #222222;
    font-weight: 700;
}

@media (max-width: 700px) {

    .pdl-box {
        padding: 20px;
    }

    .pdl-form {
        flex-direction: column;
    }

    .pdl-input,
    .pdl-button {
        width: 100%;
    }

    .pdl-info {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 450px) {

    .pdl-title {
        font-size: 23px;
    }

    .pdl-info {
        grid-template-columns: 1fr;
    }

    .pdl-box {
        padding: 16px;
    }
}

</style>


<div class="pdl-wrapper">

    <div class="pdl-box">

        <h2 class="pdl-title">
            Pinterest Downloader
        </h2>

        <p class="pdl-description">
            Download Pinterest videos and images in seconds.
        </p>


        <form
            method="post"
            class="pdl-form"
            onsubmit="
                var btn = this.querySelector('.pdl-button');
                var loading = document.getElementById('pdl-loading');

                if (btn) {
                    btn.disabled = true;
                    btn.innerText = 'Fetching...';
                }

                if (loading) {
                    loading.style.display = 'block';
                }
            "
        >

            <input
                type="url"
                name="pinterest_url"
                class="pdl-input"
                placeholder="Paste Pinterest URL here..."
                value="<?php
                    echo isset($pdl_url)
                        ? esc_attr($pdl_url)
                        : '';
                ?>"
                autocomplete="off"
                required
            >

            <input
                type="hidden"
                name="pdl_action"
                value="fetch"
            >

            <button
                type="submit"
                class="pdl-button"
            >
                Download
            </button>

        </form>


        <div
            id="pdl-loading"
            class="pdl-loading"
        >

            <span class="pdl-spinner"></span>

            Fetching Pinterest media...

        </div>


        <?php if ($pdl_error !== ''): ?>

            <div class="pdl-error">
                <?php echo esc_html($pdl_error); ?>
            </div>

        <?php endif; ?>


        <?php if (
            is_array($pdl_result) &&
            !empty($pdl_result['media']) &&
            is_array($pdl_result['media'])
        ): ?>

            <div class="pdl-results">

                <?php foreach ($pdl_result['media'] as $pdl_media): ?>

                    <?php

                    if (
                        !is_array($pdl_media) ||
                        empty($pdl_media['url'])
                    ) {
                        continue;
                    }

                    $pdl_media_url = $pdl_media['url'];

                    $pdl_media_type = !empty($pdl_media['type'])
                        ? strtolower($pdl_media['type'])
                        : 'image';

                    $pdl_width = !empty($pdl_media['width'])
                        ? $pdl_media['width']
                        : '';

                    $pdl_height = !empty($pdl_media['height'])
                        ? $pdl_media['height']
                        : '';

                    $pdl_quality = !empty($pdl_media['quality'])
                        ? $pdl_media['quality']
                        : '';

                    $pdl_duration = !empty($pdl_media['duration'])
                        ? $pdl_media['duration']
                        : '';

                    $pdl_thumbnail = !empty($pdl_media['thumbnail'])
                        ? $pdl_media['thumbnail']
                        : '';

                    ?>


                    <div class="pdl-result">


                        <?php if ($pdl_media_type === 'video'): ?>

                            <video
                                class="pdl-media"
                                controls
                                playsinline
                                preload="metadata"
                                <?php if ($pdl_thumbnail !== ''): ?>
                                    poster="<?php echo esc_url($pdl_thumbnail); ?>"
                                <?php endif; ?>
                            >

                                <source
                                    src="<?php echo esc_url($pdl_media_url); ?>"
                                    type="video/mp4"
                                >

                                Your browser does not support video playback.

                            </video>

                        <?php else: ?>

                            <img
                                class="pdl-media"
                                src="<?php echo esc_url($pdl_media_url); ?>"
                                alt="Pinterest image"
                                loading="lazy"
                            >

                        <?php endif; ?>


                        <div class="pdl-info">


                            <div class="pdl-info-item">

                                <span class="pdl-info-label">
                                    Type
                                </span>

                                <span class="pdl-info-value">
                                    <?php
                                    echo esc_html(
                                        ucfirst($pdl_media_type)
                                    );
                                    ?>
                                </span>

                            </div>


                            <?php if ($pdl_quality !== ''): ?>

                                <div class="pdl-info-item">

                                    <span class="pdl-info-label">
                                        Quality
                                    </span>

                                    <span class="pdl-info-value">
                                        <?php
                                        echo esc_html($pdl_quality);
                                        ?>
                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if (
                                $pdl_width !== '' &&
                                $pdl_height !== ''
                            ): ?>

                                <div class="pdl-info-item">

                                    <span class="pdl-info-label">
                                        Resolution
                                    </span>

                                    <span class="pdl-info-value">

                                        <?php
                                        echo esc_html(
                                            $pdl_width .
                                            ' × ' .
                                            $pdl_height
                                        );
                                        ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                            <?php if ($pdl_duration !== ''): ?>

                                <div class="pdl-info-item">

                                    <span class="pdl-info-label">
                                        Duration
                                    </span>

                                    <span class="pdl-info-value">

                                        <?php
                                        echo esc_html(
                                            $pdl_duration .
                                            ' seconds'
                                        );
                                        ?>

                                    </span>

                                </div>

                            <?php endif; ?>


                        </div>


                        <a
                            class="pdl-download"
                            href="<?php echo esc_url($pdl_media_url); ?>"
                            download
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Download <?php
                                echo $pdl_media_type === 'video'
                                    ? 'Video'
                                    : 'Image';
                            ?>
                        </a>


                    </div>

                <?php endforeach; ?>


                <?php
                $pdl_title = !empty($pdl_result['title'])
                    ? $pdl_result['title']
                    : '';
                ?>


                <?php if ($pdl_title !== ''): ?>

                    <div class="pdl-meta">

                        <span class="pdl-meta-title">
                            <?php echo esc_html($pdl_title); ?>
                        </span>

                    </div>

                <?php endif; ?>


            </div>

        <?php endif; ?>


    </div>

</div>
