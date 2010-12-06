<?php //netteCache[01]000232a:2:{s:4:"time";s:21:"0.51095400 1291395452";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:77:"C:\xampp\htdocs\spisovka1\trunk/app/../help/AdminModule/Spisznak/seznam.phtml";i:2;i:1291395418;}}}?><?php
// file …/../help/AdminModule/Spisznak/seznam.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'c848a41eca'); unset($_extends);

if (SnippetHelper::$outputAllowed) {
?>
    <h2>Seznam spisových znaků</h2>
    <p>
        Předposledním tlačítkem je položka Spisové znaky. Když na něj kliknete, zobrazí se vám soupis již existujících spisových znaků a jejich návaznost na sebe. Pokud na jeden z těchto spisových znaků kliknete, zobrazí se vám jeho detaily, které samozřejmě lze upravovat tím, že kliknete na nápis Upravit.
        V této sekci jdou ale spisové znaky i vytvářet, stačí se jen navrátit na původní obrazovku sekce Spisové znaky a kliknout na nápis Nový spisový znak. Otevře se vám nová obrazovka, která obsahuje tyto položky:
    </p>
    <dl>
        <dt>Spisový znak</dt><dd>Jedná se o povinnou položku</dd>
        <dt>Popis</dt>
        <dt>Skartační znak</dt><dd>V rozbalovacím menu vyberte jeden ze skartačních znaků</dd>
        <dt>Skartační lhůta</dt><dd>Do tohoto pole se zadává lhůta, po které dochází ke skartaci.</dd>
        <dt>Spouštěcí událost</dt><dd>V rozbalovacím menu naleznete mnoho spouštěcích událostí.</dd>
        <dt>Připojit k</dt><dd>Toto pole udává návaznost nového spisového znaku. Buď můžete vybrat možnost hlavní větev anebo jeden z již existujících spisových znaků, čímž udáte závislost na tomto znaku.</dd>
</dl>
<p>
Poté již jen najeďte a klikněte na tlačítko Vytvořit nebo můžete nový spisový
znak smazat a to tak, že kliknete na tlačítko Zrušit.
</p><?php
}
