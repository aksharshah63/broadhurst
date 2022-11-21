# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](https://keepachangelog.com/).

## [1.10.0] - 2022-09-27
### Changed
- Minimum WPForms version supported is 1.7.7.

### Fixed
- Likert and Net Promoter fields were broken in Block Editor in WordPress 5.2-5.4.
- The compatibility with the Layout field was improved.
- The Likert field was displayed incorrectly on mobile devices.

## [1.9.0] - 2022-08-30
### Changed
- Minimum WPForms version supported is 1.7.5.5.
- Improve formatting of Likert Scale entries on the Entries List and Single Entry pages.

### Fixed
- Likert Scale field row/column labels are now updated in the Form Builder preview as you type.
- Reduced code complexity and replaced improperly used variable.
- Fallback value for the Likert Scale field wasn't populated on page refresh after a failed form submission.
- Survey results were broken on mobile.
- Poll results were not shown correctly when the "Enable Poll Results" option was enabled for dynamic choices for several fields: Dropdown, Checkbox, and Multiple Choice.

## [1.8.0] - 2022-05-26
### Changed
- Minimum WPForms version supported is 1.7.4.2.

### Fixed
- WordPress 6.0 compatibility: Likert Scale and Net Promoter Score fields styling fixed inside the Full Site Editor.
- Improved compatibility with WordPress Multisite installations.
- Survey results were shown even if a form was no longer available.

## [1.7.0] - 2022-03-16
### Added
- Compatibility with WPForms 1.6.8 and the updated Form Builder.
- Compatibility with WPForms 1.7.3 and Form Revisions.
- Compatibility with WPForms 1.7.3 and search functionality on the Entries page.

### Changed
- Minimum WPForms version supported is 1.7.3.

### Fixed
- Incorrect styling of Likert Scale field with long labels.

## [1.6.4] - 2021-03-31
### Changed
- Replaced `jQuery.ready()` function with recommended way since jQuery 3.0.

### Fixed
- The "Export Entries (CSV)" link on Survey Results page.

## [1.6.3] - 2020-12-17
### Fixed
- Poll results not displaying correctly with AJAX forms.
- Form scrolls to the top when clicking on the Likert Scale field option with some themes.
- Poll results incorrectly calculate a select field with multiple selections enabled.

## [1.6.2] - 2020-08-05
### Fixed
- Survey report cache not always clearing when it should.

## [1.6.1] - 2020-04-16
### Added
- Compatibility check for WPForms v1.6.0.1.

## [1.6.0] - 2020-04-15
### Added
- Entry editing support for Net Promoter Score and Likert Scale fields. 

### Fixed
- Survey report image exports not containing white background color.

## [1.5.1] - 2020-03-03
### Changed
- Compatibility with a new version of Choices.js library in WPForms core plugin.

### Fixed
- Abandoned form entries increase survey "skipped" count.

## [1.5.0] - 2020-01-09
### Added
- Support for Access Control.

### Fixed
- PHP notice on a Print Survey results page.
- Properly display polls results votes count in a chart using `[wpforms_poll]` shortcode when there are thousands of replies. 
- Question numbering on single question print page.

## [1.4.0] - 2019-07-23
### Added
- Complete translations for French and Portuguese (Brazilian).
- Display alert when entry storage is disabled and polls are enabled.

## [1.3.2] - 2019-02-25
### Fixed
- PHP notice when printing survey results.

## [1.3.1] - 2019-02-08
### Fixed
- Typos, grammar, and other i18n related issues.

## [1.3.0] - 2019-02-06
### Added
- Complete translations for Spanish, Italian, Japanese, and German.

### Fixed
- Typos, grammar, and other i18n related issues.

## [1.2.2] - 2018-12-27
### Changed
- Likert and NPS field display priority in the form builder.

## [1.2.1] - 2018-10-19
### Fixed
- Typos with NPS form templates.

## [1.2.0] - 2018-08-28
### Added
- Net Promoter Score survey form templates.

## [1.1.0] - 2018-06-07
### Added
- Net Promoter Score field and reporting.

### Changed
- Minor styling adjustments to Likert to improve theme compatibility.

### Fixed
- Survey report print preview issue hiding empty fields.
- Not Recognizing false poll shortcode attribute values

## [1.0.0] - 2018-02-13
### Added
- Initial release.
