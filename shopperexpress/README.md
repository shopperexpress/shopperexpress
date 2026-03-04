# WordPress Base starter theme.
Modern WordPress starter theme.

## Table of contents
- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Structure](#structure)
- [Autoloading](#autoloading)
- [Coding Standards](#coding-standards)
- [DIC (Dependency Injection Container)](#dic-dependency-injection-container)
- [Gutenberg](#gutenberg)
- [FAQs](#faqs)

## Overview

This is a modern, well organized WordPress starter theme, that used [composer classes autoloading](https://getcomposer.org/doc/01-basic-usage.md#autoloading), [DIC](https://github.com/rdlowrey/auryn#auryn-) (Dependency Injection Container), [WordPress code standards](https://github.com/WordPress/WordPress-Coding-Standards#wordpress-coding-standards-for-php_codesniffer) (WPCS). Out of the box also included [Symfony VarDumper](https://symfony.com/doc/current/components/var_dumper.html#dump-examples-and-output) package, that provides a better `dump()` function that you can use instead of `var_dump()`. You can also add the packages you need while working on your project.

## Requirements
- [PHP](http://php.net/) >= 7.2.5 (Minimum required version).
- [Composer](https://getcomposer.org/) (installed globally).

## Installation

Clone or download this repository to the themes folder of a WordPress site on your development environment and run `composer install` command.

You can rename theme folder, change namespace (in this case you also should change namespace inside your composer.json file) whatever you want, but please don't forget change also text domain (using search replace in your theme folder) and don't forget rename language file of the theme.

Composer package for now in development.

## Structure

```
themes/base/                        # → Root of your theme.
├── acf-json/                       # → ACF Local JSON directory.
├── assets/                         # → Assets directory.
├── inc/                            # → PHP directory.
│   ├── Components/                 # → Contain all theme components.
│   │   ├── Ajax/                   # → Ajax directory, buy default contain one abstract class.
│   │   ├── Base/                   # → Theme base features (scripts, menus, sidebars, etc.).
│   │   ├── Gutenberg/              # → Theme Gutenberg related features.
│   │   ├── Theme.php               # → Theme main class, where all dependencies passed.
│   │   └── Theme_Component.php     # → Theme component interface.
│   ├── acf-fallback.php            # → ACF fallback functions (never edit).
│   ├── cpt.php                     # → File for custom post types registering.
│   ├── helpers.php                 # → Theme helper functions file.
│   ├── image-sizes.php             # → Theme custom image sizes.
│   ├── template-tags.php           # → Theme custom template tags.
│   └── theme-functions.php         # → Theme custom functions.
├── languages/                      # → Languages directory. 
├── template-parts/                 # → Template parts directory.
├── templates/                      # → Page templates directory.
├── vendor/                         # → Composer packages (never edit).
├── 404.php                         # → 404 template file.
├── archive.php                     # → Archive template file.
├── comments.php                    # → Comments template file.
├── composer.json                   # → Composer dependencies and scripts.
├── footer.php                      # → Footer template file.
├── functions.php                   # → Theme functions file.
├── header.php                      # → Header template file.
├── index.php                       # → Index template file.
├── page.php                        # → Page template file.
├── phpcs.xml                       # → Custom PHP Coding Standards.
├── README.md                       # → Readme MD for GitHub repository.
├── readme.txt                      # → Readme TXT for the wp.org repository.
├── screenshot.png                  # → Theme screenshot image file.
├── search.php                      # → Search template file.
├── searchform.php                  # → Search form template file.
├── sidebar.php                     # → Sidebar template file.
├── single.php                      # → Single template file.
└── style.css                       # → Theme style.css file.
```
## Autoload
In the theme used PSR-4 and composer autoload for PSR-4. You can find it in composer.json in the directive autoload.

## Coding Standards
### WordPress Coding Standard (WPCS)
In the theme used coding standarts based on [WordPress Coding Standard](https://github.com/WordPress/WordPress-Coding-Standards). In the theme disabled some rules for example naming of WordPress files for using PSR-4 autoload, short array sintax, short ternary syntax, etc.

Custom PHPCS your can find in the `phpcs.xml`.

To see errors in your code editor or IDE when you edit code in real time, you can connect the PHPCS inside your editor. You can find links with the instructions below:

- [PHPStorm](https://www.jetbrains.com/help/phpstorm/wordpress-aware-coding-assistance.html)
- [VSCODE](https://github.com/tommcfarlin/phpcs-wpcs-vscode)
- [Sublime Text](https://gist.github.com/GaryJones/0604e6cd94eae756436740ad70f92ff8)

Also, you can check PHPCS using a CLI:

```
composer cs
```

## DIC (Dependency Injection Container)

In the theme for DI used [Auryn](https://github.com/rdlowrey/auryn) recursive dependency injector. Auryn implements a PSR-11 compatible service container that allows you to standardize and centralize the way objects are constructed in your application. You can easily load your dependencies using the type hinting.

You can access injector using `App\injector()` helper function.

For example, you can execute the required method of the class that you need.

```
\App\injector()->execute( '\App\Components\Some_Class::some_method' );
```
All the theme components that will be loaded by default you can find inside the `Theme` class(inc/Components/Theme.php) `get_theme_components()` method. Each theme component should implement `Theme_Component` interface and must be added inside the`get_theme_components()` method.

## Gutenberg

By default, theme include 3 classes related to Gutenberg. Those classes responsible for things like creation of custom blocks category, defining custom color pallet and registering of ACF Gutenberg blocks that located inside template-parts/acf-blocks/ directory.

## FAQs

- **Querstion:** *Where I can add my own components?*
    - **Answer:** *It depends on what functionality this component belongs to. You can use a third-party API, for example Instagram, then create a directory inside the `Components` folder and name it `Instagram`, or it can be ajax functionality, then it should be inside the Ajax folder. I think the logic is clear. Don't forget, that your class should implement `Theme_Component` interface and add your `Theme` class, `get_theme_components()` method.*
- **Querstion:** *How can I use functions with namespaces?*
    - **Answer:** *First of all, I would recommend that you familiarize yourself with the general [concept of namespace](https://www.php.net/manual/en/language.namespaces.php). To call a function, you just need to add the name of the namespace in which this function was created, for example, inside `helpers.php` file we have namespace `App` and want to use one of functions from this file, then we simply write this `App\some_function();`, that`s it. Also you use add an alias for function, like `use function App\some_function as some_function;`.*