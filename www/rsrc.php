<?hh

require_once('./utils.php');

/**
 * Static resource distribution
 */

final class RSRC {

	public function __construct(private $resource, private $cache = false) {
		// ..
	}

	public function render(): string {
		$file = __DIR__ . '/dist/' . $this->resource;
		$type = strpos($this->resource, 'css') ? 'text/css' : 'application/x-javascript';

		if (!is_file($file)) {
			header('Status: 404 Not Found');

			return '404 File Not found';
		}

		if ($this->cache) {
			header('Cache-Control: max-age=31536000');
			header('Expires: ' . gmdate('D, j M Y H:i:s', time() + 31536000) . ' GMT');
		}

		header("Content-Type: {$type}");

		return '/*' . filemtime($file) . ",*/\n\n" . file_get_contents($file, true);
	}
}

if ($_SERVER['SERVER_ADDR'] === '127.0.0.1') {
	$file =
		$getAssetName(pathinfo(basename($_SERVER['PATH_INFO']), PATHINFO_FILENAME));
} else {
	$file =
		$getAssetName(pathinfo(basename($_GET['path']), PATHINFO_FILENAME));
}

$rsrc = new RSRC($file, true);
echo $rsrc->render();
