# Changelog
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [1.3.0] - 2022-09-21
### Changed
- Minimum WPForms version is now 1.7.5.5.

### Fixed
- Save and resume link representation was broken in the Block Editor on WordPress 5.2-5.4.

## [1.2.0] - 2022-07-14
### Added
- Display partial entry expiration in the "Entry Details" metabox when viewing entry details.
- Allow copying saved entry link from the "Entry Details" metabox when viewing entry details.

### Changed
- Partial entry is now deleted immediately after completing and submitting the form.
- Minimum WPForms version supported is 1.7.5.
- Check GDPR settings before trying to use cookies.
- Partial entries do not rely on user cookies anymore.
- Improved compatibility with Twenty Twenty-Two theme and Full Site Editing (FSE).

### Fixed
- Partial entry processing had no anti-spam protection.
- Link was displayed in the Form Builder and Elementor widget preview even if the feature was not enabled.
- Incorrect saved entry link was generated on setups with mixed HTTP/HTTPS.
- Incorrect date was displayed in the resumed form.
- Form field labels were underlined when Save and Resume was enabled.
- PHP notice was generated when email notifications were sent.

## [1.1.0] - 2022-03-16
### Added
- Compatibility with WPForms 1.7.3 and Form Revisions.
- Compatibility with WPForms 1.7.3 and search functionality on the Entries page.

### Changed
- Minimum WPForms version supported is 1.7.3.

## [1.0.1] - 2021-10-28
### Fixed
- Improved Paragraph field text formatting when restored from the saved partial entry.
- Likert Scale field values haven't been restored when multiple responses are enabled.
- Properly handle empty values for the Date / Time field and its Date Dropdown format.
- Properly restore partial entries with dynamic and/or multiple choices in checkboxes and dropdowns fields.

## [1.0.0] - 2021-10-21
### Added
- Initial release.
