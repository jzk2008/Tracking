# Tracking


## 安装 ##

建议使用 composer 进行安装:

```
composer require seetolight/tracking
```

## 使用 ##

```
use SeetoLight\Tracking\Tracking\Api;
# Pass api key parameter
$api = new Api('you api key');
#sandbox model
$api->sandbox = true;

# Create a tracking number
$data = [
    ["tracking_number" => "YT2205421266056615", "courier_code" => "yunexpress"],
    ["tracking_number" => "303662548678", "courier_code" => "qichen"],
];
$response = $api->create($data);
```