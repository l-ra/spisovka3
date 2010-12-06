<?php //netteCache[01]000234a:2:{s:4:"time";s:21:"0.93930300 1291393572";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:79:"C:\xampp\htdocs\spisovka1\trunk/app/../help/AdminModule/Nastaveni/default.phtml";i:2;i:1291393561;}}}?><?php
// file …/../help/AdminModule/Nastaveni/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'fef4434c49'); unset($_extends);

if (SnippetHelper::$outputAllowed) {
?>
    <h2>Nastavení</h2>
    <p>
        Nejprve se podíváme na sekci Nastavení. V ní lze změnit obecné informace o organizaci a také nastavení Masky čísla jednacího.
        Jestliže chcete měnit informace o organizaci stačí najet na nápis Upravit.
        Poté můžete měnit následující informace:
    </p>
    <dl>
        <dt>Název</dt><dd>Tato položka je povinnou.</dd>
        <dt>Plný název</dt><dd>Oficiální název organizace.</dd>
        <dt>Zkratka</dt><dd>Tato položka je povinnou.</dd>
        <dt>Ulice</dt>
        <dt>Město</dt>
        <dt>PSČ</dt>
        <dt>Stát</dt>
        <dt>IČ</dt>
        <dt>DIČ</dt>
        <dt>Telefon</dt>
        <dt>Email</dt>
        <dt>URL</dt><dd>Adresa vašich oficiálních stránek.</dd>
    </dl>
<p>
Jakmile máte vše vyplněné najeďte a klikněte na tlačítko Uložit.
Pokud se rozhodnete, že nechcete provádět žádné úpravy klikněte na tlačítko Zrušit.
Pokud chcete změnit masku čísla jednacího stačí jen najet na nápis Upravit pod odstavcem Čísla jednacího. Jakmile na tento nápis kliknete může zadat jedinou položku a tou je Maska. Ta se může skládat do z několika masek, které máte vypsané v tabulce pod textovým polem Maska. Tabulka zobrazuje jak označení masky, tak ukázku a také popis, který popisuje danou masku.
Jakmile máte masku čísla jednacího vytvořenou stačí kliknout na tlačítko Uložit.
V případě, že jste si úpravu masky čísla jednacího rozmysleli, klikněte na tlačítko Zrušit.
</p><?php
}
