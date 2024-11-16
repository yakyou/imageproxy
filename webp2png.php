<?php
//画像を中継する（webpだったらpngに変換する）
//PHP7.3以上でlibgdがwebpをサポートしている環境で動かしてください

//引数=画像のURL
$imageUrl = '';
if (!empty($_GET['image'])) {
	$imageUrl = trim(htmlspecialchars($_GET['image']));
}

//画像のURLにアクセスする
if (!empty($imageUrl)) {
	$ch = curl_init(); 
	curl_setopt_array($ch,
		array(
			CURLOPT_URL => $imageUrl,
			CURLOPT_SSL_VERIFYPEER =>false,
			CURLOPT_RETURNTRANSFER =>true,
			CURLOPT_HEADER => true
		) 
	);
	$source = curl_exec($ch);
	$curlInfo = curl_getinfo($ch);
	curl_close($ch);
	
	//ヘッダを分離
	$headerSize = 0;
	if (!empty($curlInfo['header_size'])) {
		$headerSize = $curlInfo['header_size'];
	}
	$strHead = substr($source, 0, $headerSize); // ヘッダ部
	$strBody = substr($source, $headerSize);    // ボディ部

	if ((strpos($strHead, 'HTTP/2 200') !== false || strpos($strHead, 'HTTP/1.1 200') !== false) && strpos($strHead, 'content-type: image/') !== false) {
		//HTTPエラーがなくてimageのときのみ

		//画像形式の検証
		$httpContentType = '';
		$fileCheckBinary = strtoupper(substr(bin2hex($strBody), 0, 24));
		if (strpos($fileCheckBinary, 'FFD8') === 0) {
			//画像を放出
			$httpContentType = 'content-type: image/jpeg';
			header($httpContentType);
			echo $strBody;
			exit;
		} else if (strpos($fileCheckBinary, '89504E47') === 0) {
			//画像を放出
			$httpContentType = 'content-type: image/png';
			header($httpContentType);
			echo $strBody;
			exit;
		} else if (strpos($fileCheckBinary, '52494646') === 0 && strpos($fileCheckBinary, '57454250') === 16) {
			//webp
			//画像を変換して放出
			webp2png($strBody);  //出力される
			exit;
		}
	}
}

//正常以外は全部エラー
header('HTTP/1.0 404 Not Found');

function webp2png($strBody) {
	//文字列からGdImageオブジェクトの取得
	$gdImage = imagecreatefromstring($strBody);
	//PNG画像を返す
	header('content-type: image/png');
	imagepng($gdImage);
}