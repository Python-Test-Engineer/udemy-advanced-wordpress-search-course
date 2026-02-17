# urlencode()

`urlencode()` is a PHP function that converts a string into a URL-safe format by encoding special characters.

## How it works

It replaces characters that have special meaning in URLs with their percent-encoded equivalents:

```php
urlencode("Hello World");  // Returns: "Hello+World"
urlencode("wordpress+php"); // Returns: "wordpress%2Bphp"
urlencode("price=$100");    // Returns: "price%3D%24100"
urlencode("name&email");    // Returns: "name%26email"
```

## Key encodings

- Space → `+` (or `%20`)
- `+` → `%2B` - important as we need the '+' to mean 'must contain'
- `&` → `%26`
- `=` → `%3D`
- `?` → `%3F`
- `#` → `%23`
- `/` → `%2F`

## Common usage in WordPress/PHP

```php
// Building a search URL
$search_term = "wordpress & php";
$url = home_url('/?s=' . urlencode($search_term));
// Result: http://yoursite.com/?s=wordpress+%26+php

// Adding query parameters
$params = array(
    'category' => 'web development',
    'tags' => 'php+mysql'
);

foreach($params as $key => $value) {
    $encoded_params[] = $key . '=' . urlencode($value);
}
$query_string = implode('&', $encoded_params);
// Result: category=web+development&tags=php%2Bmysql
```

## `urlencode()` vs `rawurlencode()`

- `urlencode()` - Encodes spaces as `+` (better for query strings)
- `rawurlencode()` - Encodes spaces as `%20` (better for path components)

Most of the time with query strings, `urlencode()` is what you want.