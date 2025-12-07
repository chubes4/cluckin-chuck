# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1] - 2025-12-07

### Added
- Wing Review Submit plugin for frontend form submissions
- Geocoding functionality using Nominatim API
- Theme-owned metadata system with Wing_Location_Meta class
- Build scripts for production deployment
- Frontend styles registration in block.json files

### Changed
- Restructured architecture: theme owns CPT, 4 separate plugins
- All plugins now consume data from theme metadata
- Improved plugin isolation and development workflow

### Fixed
- Critical security vulnerability in form handling
- Missing frontend styles for map and submit blocks

### Security
- Added nonce verification to all form submissions
- Implemented honeypot spam protection
- Added rate limiting (1 submission per hour per IP)