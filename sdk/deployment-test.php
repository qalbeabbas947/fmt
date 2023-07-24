<?php
    require_once 'freemius/FreemiusBase.php';
    require_once 'freemius/Freemius.php';

    define( 'FS__API_SCOPE', 'developer' );
    define( 'FS__API_DEV_ID', 12667 );
    define( 'FS__API_PUBLIC_KEY', 'pk_24e33b2347da81eeaec4096704bb0' );
    define( 'FS__API_SECRET_KEY', 'sk_EaNG<}jZRM<I@Ehz!dySmltt@i.3C' );

    // Init SDK.
    $api = new Freemius_Api(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);

    // Deploy new version.
    $tag = $api->Api('plugins/115/tags.json', 'POST', array(
        'add_contributor' => true
    ), array(
        'file' => 'C:\xampp\htdocs\deployment-via-sdk\my-plugin.zip'
    ));

    // Generate secure download URLs.
    $free_version_download_url = $api->GetSignedUrl( "/plugins/{$tag->plugin_id}/tags/{$tag->id}.zip?is_premium=false" );
    $paid_version_download_url = $api->GetSignedUrl( "/plugins/{$tag->plugin_id}/tags/{$tag->id}.zip?is_premium=true" );

    // Download the paid version.
    if ( file_put_contents($local_file_path, file_get_contents($paid_version_download_url)) ) {
        // Successfully downloaded and stored.
    }
    
    print_r($tag);
