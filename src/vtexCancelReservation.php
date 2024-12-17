<?php

use Carbon\Carbon;

// Change here to your Vtex Account name, Appkey and AppToken
$vtexAccountName = "";
$vtexApiAppkey = "";
$vtexApiApptoken = "";

// Change here to warehouseId that you whant to cancel reservation
$warehouseId = "1_1";

$now = Carbon::now();
$page = 1;
do {
    echo "Page: $page \n";
    
    $responseSkus = vtexRequest($vtexAccountName, $vtexApiAppkey, $vtexApiApptoken, "GET", "/api/catalog_system/pvt/sku/stockkeepingunitids?pagesize=$pagesize&page=$page");
    $page++;
    foreach($responseSkus["ResponseBody"] as $skuId) {
        echo "SkuID: $skuId\n";
        $responseReservation = vtexRequest($vtexAccountName, $vtexApiAppkey, $vtexApiApptoken, "GET", "/api/logistics/pvt/inventory/reservations/$warehouseId/$skuId");
        
        foreach ($responseReservation["ResponseBody"]->items as $reservation) {
            if($end = Carbon::parse($reservation->MaximumConfirmationDateUtc) < $now ) {
                echo $reservation->LockId . "\n";
                vtexRequest($vtexAccountName, $vtexApiAppkey, $vtexApiApptoken, "POST", "/api/logistics/pvt/inventory/reservations/$reservation->LockId/cancel");
            }
        }
    }
} while (count($responseSkus["ResponseBody"]) > 0);

function vtexRequest( string $vtexAccountName, string $vtexApiAppkey, string $vtexApiApptoken, string $method, string $path, array|object $body = []) {
    $url = "https://$vtexAccountName.vtexcommercestable.com.br$path";

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => array(
            "Accept: application/json",
            "Content-Type: application/json",
            "X-VTEX-API-AppKey: $vtexApiAppkey",
            "X-VTEX-API-AppToken: $vtexApiApptoken"
        ),
    ));

    $response = curl_exec($curl);

    return  [
        "ResponseCode" => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        "ResponseBody"  =>  json_decode($response)
        ];
}
