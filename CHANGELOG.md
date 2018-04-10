# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [0.0.3] - 2018-04-10
### Added
- `ace users` : get all users
- `ace tasks:assign` and `ace tasks:unassign` Assign a user to a project and vice verca.
- show hours items formatted by d,h,m (eg: 3.29 = 3h 17m)
- `projects:totals` : shows project stats

### Fixed
- Login for a timed out sessions

### Changed
- Refactored sourcecode for better readability and maintainability. Instead of one huge php file for the whole app it is now structured into modules (src/commands dir)