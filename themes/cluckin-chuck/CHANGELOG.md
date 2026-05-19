# Changelog

## 0.1.3

- Geocoder now retries with suite/unit/apartment fragments stripped when Nominatim returns no results (fixes "Suite 301"-style addresses geocoding to wrong coordinates).
- Geocoder requests now scope to US results and send `Accept-Language: en`.
- `cluckin-chuck/update-location` ability now auto-re-geocodes when `wing_address` changes and explicit coordinates are not provided. Returns new `geocoded` boolean in the response.

## 0.1.2

- Previous release.
