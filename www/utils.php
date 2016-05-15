<?hh

/**
 * Hash string
 */
$hashString = ($name) ==>
	substr(base64_encode(sha1($name)), 0, 11);

/**
 * Asset map
 */
$assetDomain = '';
$assetVersion = 'v1';
$assetMap = array(
	$hashString('style.css') => 'style.css',
	$hashString('app.js') => 'app.js',
);

$getAssetName = ($hash) ==>
	@$assetMap[$hash];

$getAssetSrc = ($name, $type) ==> 
	"{$assetDomain}/rsrc.php/{$assetVersion}/{$hashString($name . '.' . $type)}.{$type}";