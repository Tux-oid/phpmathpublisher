# Web publishing system for mathematical documents  

This is an updated version of the orginal PhpMathPublisher script by Pascal Brachet. The script has been transformed
into proper PSR compliant, object oriented code.

##Features

With PhpMathPublisher, you can publish mathematical documents on the web by using only a php script (no latex programs on the server, no mathml...).

*   **The method :** each formula is transformed into a PNG image which can be embedded with the corresponding html code
*   **Advantages for the visitors :** the graphics created by PhpMathPublisher can be displayed by all the current
    browsers and can easily be embedded in other formats like PDF.  
    The visitor does not need to install anything on their system: neither fonts, nor plugins...
*   **Advantages for the webmasters :**
    *   Only Php (with the GD library) and some fonts (already included) are required.
    *   PhpMathPublisher is simple to install and use. You can convert a mathematical text with only one php command.
    *   It can be easily used by CMS systems, weblogs and forums.
    *   PhpMathPublisher creates cross-browser pages.
    *   The png images created by the script can be transparent and can be placed in any page.

##Installation and Usage

The easiest method is installation by composer

    FIXME update readme when published to composer


Example usage:

    $pmp = new PhpMathPublisher(__DIR__ . '/images', 'images', 16);
    echo $pmp->mathFilter('This is some html with a formula embdded: <m>a^2+b^2=c^2</m>);

Check out the ``example.php`` script to learn more.

##License

This program is licensed to you under the terms of the GNU General Public License Version 2 as published by the Free Software Foundation.

Original [PhpMathPublisher](http://www.xm1math.net/phpmathpublisher/) - Copyright 2005 **Pascal Brachet - France**
The author is a teacher of mathematics in a French secondary school (Lycée Bernard Palissy - Agen).  


