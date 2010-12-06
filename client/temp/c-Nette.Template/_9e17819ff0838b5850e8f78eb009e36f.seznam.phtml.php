<?php //netteCache[01]000233a:2:{s:4:"time";s:21:"0.93022400 1291394146";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:78:"C:\xampp\htdocs\spisovka1\trunk/app/../help/AdminModule/Opravneni/seznam.phtml";i:2;i:1291394074;}}}?><?php
// file …/../help/AdminModule/Opravneni/seznam.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'f3a3a344f9'); unset($_extends);

if (SnippetHelper::$outputAllowed) {
?>
    <h2>Seznam roli</h2>
    <p>
    Pod tlačítkem Oprávnění se nachází seznam rolí, které mají nějaká oprávnění. Takže ne každá role bude mít přístup do sekce Administrace.
    Seznam rolí je úhledně upraven do přehledné tabulky, ve které je na každém řádku název role, její popis, kódové označení a informace o dědění role.
    Pokud chcete vidět a upravovat Oprávnění pro již existující role, stačí najet a kliknout na jeden z názvů rolí. Otevře se vám nová obrazovka se základními informacemi a oprávněním. Základní informace můžete upravovat tak, že kliknete na nápis Upravit.
    V případě, že budete chtít upravovat oprávnění dané role, pod základními informacemi naleznete soupis oprávnění přístupu do různých sekcí. Na konci každého řádku se nacházejí dvě zaškrtávací pole. Když zaškrtnete první, tak tím dané oprávnění povolujete, pokud ho chcete naopak zakázat, zaškrtněte druhé pole. Jakmile máte všechny změny zaškrtané, najeďte do dolní části obrazovky a klikněte na tlačítko Upravit oprávnění. V horní části okna budete informování o úspěšném změnění práv.
    Pokud chcete do seznamu přidat ještě nějakou další roli, stačí najet na nápis Nová role a kliknout na něj. Po otevření nové obrazovky na vás čeká formulář s následujícími položkami:
    </p>
    <dl>
        <dt>Název role</dt><dd>Jedná se o první povinnou položku formuláře.</dd>
        <dt>Kódové označení</dt><dd>Toto je druhá povinná položka.</dd>
        <dt>Popis role</dt>
        <dt>Dědí z role</dt><dd>U této položky máte možnost v rozbalovacím menu vybrat jednu z rolí, která bude určovat oprávnění i pro vaši novou roli.</dd>
    </dl>
    <p>
    Jakmile máte vše nastavené, stačí kliknout na tlačítko Vytvořit. Pokud jste se rozhodli, že nepotřebujete vytvářet novou roli, klikněte na tlačítko Zrušit.
    </p><?php
}
