# Pressbooks Plugin Scaffold 
**Contributors:** conner_bw, greatislander  
**Donate link:** https://pressbooks.org/donate/  
**Tags:** pressbooks, plugin, scaffolding  
**Requires at least:** 4.9.7  
**Tested up to:** 4.9.7  
**Stable tag:** 0.3.1  
**License:** GPLv3 or later  
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html  

Scaffolding for a Pressbooks plugin.


## Description 

This is not a plugin, but a tool that helps you scaffold a plugin.


## Installation 


# Create Plugin 

Run `composer create-project pressbooks/pressbooks-plugin-scaffold <your-plugin-slug>`.

Run `yarn` to install dependencies.

Uncomment lines 34-43 of `pressbooks-plugin-scaffold.php` to enable Composer autoloader (you'll need to require a class to test for first).

Replace `pressbooks/pressbooks-plugin-slug` with `<your-github-username>/<your-plugin-slug>` throughout the project.

Replace `pressbooks-plugin-slug` with `<your-plugin-slug>` throughout the project (renaming files as needed).

Replace `PressbooksPluginScaffold` with `<YourNamespace>` throughout the project.


# Optional Steps 

Configure Travis deploys (instructions to come).

Configure Transifex project and localization (instructions to come).


# Helpful Commands 

`composer standards`: check PHP coding standards with PHP_CodeSniffer
`composer test`: run unit tests with PHPUnit
`composer readme`: generate a Markdown readme from readme.txt
`composer localize`: update localization files (requires Transifex to be configured)
`yarn run test`: check SCSS/ES6 with StyleLint and ESLint
`yarn run build:production`: build assets for distribution


## Frequently Asked Questions 

N/A.


## Screenshots 

N/A.


## Changelog 


### 0.2.0 
**Major Changes**
- A new feature.

**Minor Changes**
- A backwards-compatible change.

**Patches**
- A bug fix.


## Upgrade Notice 

Pressbooks Plugin Scaffold requires Pressbooks >= 5.2.0 and WordPress >= 4.9.5.
