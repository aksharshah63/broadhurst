Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [1.4.0] - 2022-05-26
### Added
- Compatibility with WPForms 1.6.8 and the updated Form Builder.
- Compatibility with the Rich Text field.

### Changed
- Improved compatibility with jQuery 3.5 and no jQuery Migrate plugin.
- Show settings in the Form Builder only if they are enabled.
- Show a modal in the Form Builder about available additional options only once.

### Fixed
- `0` values were not allowed to save as custom post meta.
- Images added as Post Featured Images didn't have titles.
- Events Calendar plugin compatibility: start/end times had incorrect timezone.

## [1.3.2] - 2020-12-17
### Fixed
- Modern file upload processing when files were saved to the custom post meta table.

## [1.3.1] - 2020-08-05
### Fixed
- Modern file upload is not compatible with the ACF File field when used in custom post meta.

## [1.3.0] - 2019-01-15
### Added
- Access Controls compatibility (WPForms 1.5.8).

## [1.2.1] - 2019-11-07
### Fixed
- Compatibility with a modern file uploader (from WPForms 1.5.6) for featured image.

## [1.2.0] - 2019-07-23
### Added
- Complete translations for French and Portuguese (Brazilian).

## [1.1.1] - 2019-02-26
### Fixed
- Post submission featured image not showing up as "Featured Image" in WordPress post type.
- Post submission featured image thumbnail not displayed in WordPress Media Library.
- Post can be submitted without an author (now falls back to form author).

## [1.1.0] - 2019-02-06
### Added
- Complete translations for Spanish, Italian, Japanese, and German.

### Fixed
- Typos, grammar, and other i18n related issues.

## [1.0.4] - 2017-08-21
### Changed
- Template uses new `core` property so it displays with other core templates.

## [1.0.3] - 2017-03-09
### Added
- Improved integration for storing The Events Calendar post meta.

## [1.0.2] - 2017-02-22
### Fixed
- Capitalized letters not being allowed in custom post meta keys.

## [1.0.1] - 2017-01-17
### Fixed
- Possible error if custom meta fields were setup but incomplete.

## [1.0.0] - 2016-10-5
- Initial release.
