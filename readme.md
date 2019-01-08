
install

```json
{
    "require": {
        "epii/route": ">=0.0.1"
    }
}
```


```php

Route::get("/test", function () {

});

Route::get("/test","class@method");

Route::get("/{id}:(\\d+)", function ($name) {
    var_dump($name);
});

Route::get("/:(.*?\\.html)", function ($m) {
    var_dump($m);
});

Route::delete("/book/{id}:(\\d+)", "class@delete");

```
 
