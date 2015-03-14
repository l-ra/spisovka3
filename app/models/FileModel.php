<?php

//'main','enclosure','signature','meta'

class FileModel extends BaseModel
{

    protected $name = 'file';
    protected $primary = 'id';


    public function getInfo($file_id, $file_version = null)
    {

        $result = $this->select(array(array('id=%i',$file_id)));
        $row = $result->fetch();

        if ( $row ) {

            $user = UserModel::getIdentity($row->user_created);
            $row->user_name = Osoba::displayName($user);
            $row->typ_name = FileModel::typPrilohy($row->typ, 1);
            // Ignoruj mime-type ulozeny v databazi (nastaveny pri nahrani prilohy) a zjisti jej pokazde znovu
            $row->mime_type = FileModel::mimeType($row->real_path);
            // Osetreni ikony - pokud neexistuje, pak nahradit defaultni
            $mime_type_webalize = String::webalize($row->mime_type);
            $mime_type_icon = APP_DIR ."/../public/images/mimetypes/". $mime_type_webalize .".png" ;
            if ( @file_exists($mime_type_icon) ) {
                $row->mime_type_icon = Nette\Environment::getVariable('publicUrl') ."images/mimetypes/". $mime_type_webalize .".png";
            } else {
                $row->mime_type_icon = Nette\Environment::getVariable('publicUrl') ."images/mimetypes/application-octet-stream.png";
            }            
            
            
            return $row;
        } else {
            return null;
        }

    }

    public function seznam($vse=0, $dokument_id=null, $dokument_version=null) {

        $select = $this->select(null, array('nazev'));
        $rows = $select->fetchAll();

        $tmp = array();
        foreach ($rows as $file) {
            
            $user = UserModel::getIdentity($file->user_created);
            $file->user_name = Osoba::displayName($user);
            $file->typ_name = FileModel::typPrilohy($file->typ, 1);
            // Nahrazeni online mime-type
            $file->mime_type = FileModel::mimeType($file->real_path);
            // Osetreni ikony - pokud neexistuje, pak nahradit defaultni
            $mime_type_webalize = String::webalize($file->mime_type);
            $mime_type_icon = APP_DIR ."/../public/images/mimetypes/". $mime_type_webalize .".png" ;
            if ( @file_exists($mime_type_icon) ) {
                $file->mime_type_icon = Nette\Environment::getVariable('publicUrl') ."images/mimetypes/". $mime_type_webalize .".png";
            } else {
                $file->mime_type_icon = Nette\Environment::getVariable('publicUrl') ."images/mimetypes/application-octet-stream.png";
            }             
            
            $tmp[ $file->id ] = $file;


        }

        return ($rows) ? $rows : NULL;

    }

    public function vlozit($data) {

        $row = array();
        $row['typ'] = isset($data['typ'])?$data['typ']:1;
        $row['nazev'] = $data['nazev'];
        $row['popis'] = isset($data['popis'])?$data['popis']:'';
        $row['real_name'] = $data['real_name'];
        $row['real_path'] = $data['real_path'];
        $row['real_type'] = isset($data['real_type'])?$data['real_type']:'UploadFile_Basic';

        $row['mime_type'] = FileModel::mimeType($row['real_path']);

        if ( !isset($data['md5_hash']) ) {
            if ( file_exists($data['real_path']) ) {
                $row['md5_hash'] = md5_file($data['real_path']);
            } else {
                $row['md5_hash'] = '';
            }
        } else {
            $row['md5_hash'] = $data['md5_hash'];
        }

        if ( !isset($data['size']) ) {
            if ( file_exists($data['real_path']) ) {
                $row['size'] = filesize($data['real_path']);
            } else {
                $row['size'] = -1;
            }
        } else {
            $row['size'] = $data['size'];
        }

        $row['date_created'] = new DateTime();
        $row['user_created'] = Nette\Environment::getUser()->getIdentity()->id;
        $row['date_modified'] = new DateTime();
        $row['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;

        $row['guid'] = UUID::v4();

        // ulozeni
        $row['stav'] = 1;

        //Debug::dump($row); exit;

        $file_id = $this->insert($row);

        if ( $file_id ) {
            return $this->getInfo($file_id);
        } else {
            return false;
        }

    }

    public function upravitMetadata($data, $file_id) {


        $file_info = $this->select(array(array('id=%i',$file_id)))->fetch();
        if ( !$file_info ) return false;

        $file_info = $this->obj2array($file_info);

        $row = $file_info;
        $row['typ'] = isset($data['typ'])?$data['typ']:1;
        $row['nazev'] = $data['nazev'];
        $row['popis'] = isset($data['popis'])?$data['popis']:'';

        $row['date_modified'] = new DateTime();
        $row['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;

        if ( $this->update($row, array('id=%i',$file_id)) ) {
            return $this->getInfo($file_id);
        } else {
            return false;
        }


    }

    protected function odebrat($data) {
        
    }

    public function  deleteAll() {

        $DokumentPrilohy = new DokumentPrilohy();
        $DokumentPrilohy->deleteAll();

        //$FileHistorie = new FileHistorie();
        //$FileHistorie->deleteAll();

        parent::deleteAll();
    }

    public static function typPrilohy($typ=null , $out=0) {

        $enum_orig = array('1'=>'main',
                           '2'=>'enclosure',
                           '3'=>'signature',
                           '4'=>'meta',
                           '5'=>'source'
                     );
        $enum_popis = array('1'=>'hlavní soubor',
                            '2'=>'příloha',
                            '3'=>'podpis',
                            '4'=>'metadata',
                            '5'=>'zdrojový soubor'
                     );

        if ( is_null($typ) ) {
            return $enum_popis;
        }
        if ( $out == 0 ) {
            return ( array_key_exists($typ, $enum_orig) )?$enum_orig[ $typ ]:null;
        } else if ( $out == 1 ) {
            return ( array_key_exists($typ, $enum_popis) )?$enum_popis[ $typ ]:null;
        } else {
            return null;
        }

    }

    public static function copy($source, $destination) {

        if (!($handle_src = fopen($src, "rb")))
            return false;

        if (!($handle_dst = fopen($dst, "wb"))) {
            fclose($handle_src);
            return false;
        }

        if (flock($handle_dst, LOCK_EX)) {
            while (!feof($handle_src)) {
                if ($data = fread($handle_src, 1024)) {
                    if (!fwrite($handle_dst, $data))
                        return false;
                }
                else
                    return false;
            }
            if (!flock($handle_dst, LOCK_UN))
            return false;
        }
        else
            return false;

        if (!fclose($handle_src) || !fclose($handle_dst))
            return false;

        return true;
    }

    public static function mimeType($filename) {

        $mime_types = array (
            '' => 'application/octet-stream',
            '%' => 'application/x-trash',
            '3gp' => 'video/3gpp',
            '7z' => 'application/x-7z-compressed',
            'abw' => 'application/x-abiword',
            'acx' => 'application/internet-property-stream',
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'alc' => 'chemical/x-alchemy',
            'amr' => 'audio/amr',
            'anx' => 'application/annodex',
            'apk' => 'application/vnd.android.package-archive',
            'art' => 'image/x-jg',
            'asc' => 'text/plain',
            'asf' => 'video/x-ms-asf',
            'asn' => 'chemical/x-ncbi-asn1-spec',
            'aso' => 'chemical/x-ncbi-asn1-binary',
            'asr' => 'video/x-ms-asf',
            'asx' => 'video/x-ms-asf',
            'atom' => 'application/atom+xml',
            'atomcat' => 'application/atomcat+xml',
            'atomsrv' => 'application/atomserv+xml',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'awb' => 'audio/amr-wb',
            'axa' => 'audio/annodex',
            'axs' => 'application/olescript',
            'axv' => 'video/annodex',
            'b' => 'chemical/x-molconn-Z',
            'bak' => 'application/x-trash',
            'bas' => 'text/plain',
            'bat' => 'application/x-msdos-program',
            'bcpio' => 'application/x-bcpio',
            'bib' => 'text/x-bibtex',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/x-ms-bmp',
            'boo' => 'text/x-boo',
            'book' => 'application/x-maker',
            'brf' => 'text/plain',
            'bsd' => 'chemical/x-crossfire',
            'bsr' => 'application/x-bsr',
            'c' => 'text/x-csrc',
            'c++' => 'text/x-c++src',
            'c3d' => 'chemical/x-chem3d',
            'cab' => 'application/x-cab',
            'cac' => 'chemical/x-cache',
            'cache' => 'chemical/x-cache',
            'cap' => 'application/cap',
            'cascii' => 'chemical/x-cactvs-binary',
            'cat' => 'application/vnd.ms-pki.seccat',
            'cbin' => 'chemical/x-cactvs-binary',
            'cbr' => 'application/x-cbr',
            'cbz' => 'application/x-cbz',
            'cc' => 'text/x-c++src',
            'cda' => 'application/x-cdf',
            'cdf' => 'application/x-cdf',
            'cdr' => 'image/x-coreldraw',
            'cdt' => 'image/x-coreldrawtemplate',
            'cdx' => 'chemical/x-cdx',
            'cdy' => 'application/vnd.cinderella',
            'cef' => 'chemical/x-cxf',
            'cer' => 'chemical/x-cerius',
            'chm' => 'chemical/x-chemdraw',
            'chrt' => 'application/x-kchart',
            'cif' => 'chemical/x-cif',
            'class' => 'application/java-vm',
            'clp' => 'application/x-msclip',
            'cls' => 'text/x-tex',
            'cmdf' => 'chemical/x-cmdf',
            'cml' => 'chemical/x-cml',
            'cmx' => 'image/x-cmx',
            'cod' => 'application/vnd.rim.cod',
            'com' => 'application/x-msdos-program',
            'cpa' => 'chemical/x-compass',
            'cpio' => 'application/x-cpio',
            'cpp' => 'text/x-c++src',
            'cpt' => 'image/x-corelphotopaint',
            'cr2' => 'image/x-canon-cr2',
            'crd' => 'application/x-mscardfile',
            'crl' => 'application/x-pkcs7-crl',
            'crt' => 'application/x-x509-ca-cert',
            'crw' => 'image/x-canon-crw',
            'csf' => 'chemical/x-cache-csf',
            'csh' => 'text/x-csh',
            'csm' => 'chemical/x-csml',
            'csml' => 'chemical/x-csml',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'ctab' => 'chemical/x-cactvs-binary',
            'ctx' => 'chemical/x-ctx',
            'cu' => 'application/cu-seeme',
            'cub' => 'chemical/x-gaussian-cube',
            'cxf' => 'chemical/x-cxf',
            'cxx' => 'text/x-c++src',
            'd' => 'text/x-dsrc',
            'dat' => 'application/x-ns-proxy-autoconfig',
            'davmount' => 'application/davmount+xml',
            'dcr' => 'application/x-director',
            'deb' => 'application/x-debian-package',
            'der' => 'application/x-x509-ca-cert',
            'dif' => 'video/dv',
            'diff' => 'text/x-diff',
            'dir' => 'application/x-director',
            'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dl' => 'video/dl',
            'dll' => 'application/x-msdos-program',
            'dmg' => 'application/x-apple-diskimage',
            'dms' => 'application/x-dms',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dot' => 'application/msword',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dv' => 'video/dv',
            'dvi' => 'application/x-dvi',
            'dx' => 'chemical/x-jcamp-dx',
            'dxr' => 'application/x-director',
            'emb' => 'chemical/x-embl-dl-nucleotide',
            'embl' => 'chemical/x-embl-dl-nucleotide',
            'eml' => 'message/rfc822',
            'ent' => 'chemical/x-pdb',
            'eps' => 'application/postscript',
            'eps2' => 'application/postscript',
            'eps3' => 'application/postscript',
            'epsf' => 'application/postscript',
            'epsi' => 'application/postscript',
            'erf' => 'image/x-epson-erf',
            'es' => 'application/ecmascript',
            'etx' => 'text/x-setext',
            'evy' => 'application/envoy',
            'exe' => 'application/x-msdos-program',
            'ez' => 'application/andrew-inset',
            'fb' => 'application/x-maker',
            'fbdoc' => 'application/x-maker',
            'fch' => 'chemical/x-gaussian-checkpoint',
            'fchk' => 'chemical/x-gaussian-checkpoint',
            'fif' => 'application/fractals',
            'fig' => 'application/x-xfig',
            'flac' => 'audio/flac',
            'fli' => 'video/fli',
            'flr' => 'x-world/x-vrml',
            'flv' => 'video/x-flv',
            'fm' => 'application/x-maker',
            'fo' => 'application/vnd.software602.filler.form+xml',
            'frame' => 'application/x-maker',
            'frm' => 'application/x-maker',
            'gal' => 'chemical/x-gaussian-log',
            'gam' => 'chemical/x-gamess-input',
            'gamin' => 'chemical/x-gamess-input',
            'gau' => 'chemical/x-gaussian-input',
            'gcd' => 'text/x-pcs-gcd',
            'gcf' => 'application/x-graphing-calculator',
            'gcg' => 'chemical/x-gcg8-sequence',
            'gen' => 'chemical/x-genbank',
            'gf' => 'application/x-tex-gf',
            'gif' => 'image/gif',
            'gjc' => 'chemical/x-gaussian-input',
            'gjf' => 'chemical/x-gaussian-input',
            'gl' => 'video/gl',
            'gnumeric' => 'application/x-gnumeric',
            'gpt' => 'chemical/x-mopac-graph',
            'gsf' => 'application/x-font',
            'gsm' => 'audio/x-gsm',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'h' => 'text/x-chdr',
            'h++' => 'text/x-c++hdr',
            'hdf' => 'application/x-hdf',
            'hh' => 'text/x-c++hdr',
            'hin' => 'chemical/x-hin',
            'hlp' => 'application/winhlp',
            'hpp' => 'text/x-c++hdr',
            'hqx' => 'application/mac-binhex40',
            'hs' => 'text/x-haskell',
            'hta' => 'application/hta',
            'htc' => 'text/x-component',
            'htm' => 'text/html',
            'html' => 'text/html',
            'htt' => 'text/webviewhtml',
            'hxx' => 'text/x-c++hdr',
            'ica' => 'application/x-ica',
            'ice' => 'x-conference/x-cooltalk',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'icz' => 'text/calendar',
            'ief' => 'image/ief',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'iii' => 'application/x-iphone',
            'info' => 'application/x-info',
            'inp' => 'chemical/x-gamess-input',
            'ins' => 'application/x-internet-signup',
            'iso' => 'application/x-iso9660-image',
            'isp' => 'application/x-internet-signup',
            'ist' => 'chemical/x-isostar',
            'istr' => 'chemical/x-isostar',
            'jad' => 'text/vnd.sun.j2me.app-descriptor',
            'jam' => 'application/x-jam',
            'jar' => 'application/java-archive',
            'java' => 'text/x-java',
            'jdx' => 'chemical/x-jcamp-dx',
            'jfif' => 'image/pipeg',
            'jmz' => 'application/x-jmol',
            'jng' => 'image/x-jng',
            'jnlp' => 'application/x-java-jnlp-file',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/javascript',
            'kar' => 'audio/midi',
            'key' => 'application/pgp-keys',
            'kil' => 'application/x-killustrator',
            'kin' => 'chemical/x-kinemage',
            'kml' => 'application/vnd.google-earth.kml+xml',
            'kmz' => 'application/vnd.google-earth.kmz',
            'kpr' => 'application/x-kpresenter',
            'kpt' => 'application/x-kpresenter',
            'ksp' => 'application/x-kspread',
            'kwd' => 'application/x-kword',
            'kwt' => 'application/x-kword',
            'latex' => 'application/x-latex',
            'lha' => 'application/x-lha',
            'lhs' => 'text/x-literate-haskell',
            'lin' => 'application/bbolin',
            'lsf' => 'video/x-la-asf',
            'lsx' => 'video/x-la-asf',
            'ltx' => 'text/x-tex',
            'lyx' => 'application/x-lyx',
            'lzh' => 'application/x-lzh',
            'lzx' => 'application/x-lzx',
            'm13' => 'application/x-msmediaview',
            'm14' => 'application/x-msmediaview',
            'm3g' => 'application/m3g',
            'm3u' => 'audio/x-mpegurl',
            'm4a' => 'audio/mpeg',
            'maker' => 'application/x-maker',
            'man' => 'application/x-troff-man',
            'manifest' => 'text/cache-manifest',
            'mcif' => 'chemical/x-mmcif',
            'mcm' => 'chemical/x-macmolecule',
            'mdb' => 'application/msaccess',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mht' => 'message/rfc822',
            'mhtml' => 'message/rfc822',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mif' => 'application/x-mif',
            'mkv' => 'video/x-matroska',
            'mm' => 'application/x-freemind',
            'mmd' => 'chemical/x-macromodel-input',
            'mmf' => 'application/vnd.smaf',
            'mml' => 'text/mathml',
            'mmod' => 'chemical/x-macromodel-input',
            'mng' => 'video/x-mng',
            'mny' => 'application/x-msmoney',
            'moc' => 'text/x-moc',
            'mol' => 'chemical/x-mdl-molfile',
            'mol2' => 'chemical/x-mol2',
            'moo' => 'chemical/x-mopac-out',
            'mop' => 'chemical/x-mopac-input',
            'mopcrt' => 'chemical/x-mopac-input',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mpa' => 'video/mpeg',
            'mpc' => 'chemical/x-mopac-input',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpega' => 'audio/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'mpp' => 'application/vnd.ms-project',
            'mpv' => 'video/x-matroska',
            'mpv2' => 'video/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'msi' => 'application/x-msi',
            'mvb' => 'chemical/x-mopac-vib',
            'mxf' => 'application/mxf',
            'mxu' => 'video/vnd.mpegurl',
            'nb' => 'application/mathematica',
            'nbp' => 'application/mathematica',
            'nc' => 'application/x-netcdf',
            'nef' => 'image/x-nikon-nef',
            'nwc' => 'application/x-nwc',
            'nws' => 'message/rfc822',
            'o' => 'application/x-object',
            'oda' => 'application/oda',
            'odb' => 'application/vnd.oasis.opendocument.database',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'odf' => 'application/vnd.oasis.opendocument.formula',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'odi' => 'application/vnd.oasis.opendocument.image',
            'odm' => 'application/vnd.oasis.opendocument.text-master',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'oga' => 'audio/ogg',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'old' => 'application/x-trash',
            'orf' => 'image/x-olympus-orf',
            'otg' => 'application/vnd.oasis.opendocument.graphics-template',
            'oth' => 'application/vnd.oasis.opendocument.text-web',
            'otp' => 'application/vnd.oasis.opendocument.presentation-template',
            'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'ott' => 'application/vnd.oasis.opendocument.text-template',
            'oza' => 'application/x-oz-application',
            'p' => 'text/x-pascal',
            'p10' => 'application/pkcs10',
            'p12' => 'application/x-pkcs12',
            'p7b' => 'application/x-pkcs7-certificates',
            'p7c' => 'application/x-pkcs7-mime',
            'p7m' => 'application/x-pkcs7-mime',
            'p7r' => 'application/x-pkcs7-certreqresp',
            'p7s' => 'application/x-pkcs7-signature',
            'pac' => 'application/x-ns-proxy-autoconfig',
            'pas' => 'text/x-pascal',
            'pat' => 'image/x-coreldrawpattern',
            'patch' => 'text/x-diff',
            'pbm' => 'image/x-portable-bitmap',
            'pcap' => 'application/cap',
            'pcf' => 'application/x-font',
            'pcf.Z' => 'application/x-font',
            'pcx' => 'image/pcx',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pfa' => 'application/x-font',
            'pfb' => 'application/x-font',
            'pfx' => 'application/x-pkcs12',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'pgp' => 'application/pgp-signature',
            'php' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php3',
            'php3p' => 'application/x-httpd-php3-preprocessed',
            'php4' => 'application/x-httpd-php4',
            'php5' => 'application/x-httpd-php5',
            'phps' => 'application/x-httpd-php-source',
            'pht' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'pk' => 'application/x-tex-pk',
            'pko' => 'application/ynd.ms-pkipko',
            'pl' => 'text/x-perl',
            'pls' => 'audio/x-scpls',
            'pm' => 'text/x-perl',
            'pma' => 'application/x-perfmon',
            'pmc' => 'application/x-perfmon',
            'pml' => 'application/x-perfmon',
            'pmr' => 'application/x-perfmon',
            'pmw' => 'application/x-perfmon',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'pot' => 'text/plain',
            'pot,' => 'application/vnd.ms-powerpoint',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'ppm' => 'image/x-portable-pixmap',
            'pps' => 'application/vnd.ms-powerpoint',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'prf' => 'application/pics-rules',
            'prt' => 'chemical/x-ncbi-asn1-ascii',
            'ps' => 'application/postscript',
            'psd' => 'image/x-photoshop',
            'pub' => 'application/x-mspublisher',
            'py' => 'text/x-python',
            'pyc' => 'application/x-python-code',
            'pyo' => 'application/x-python-code',
            'qgs' => 'application/x-qgis',
            'qt' => 'video/quicktime',
            'qtl' => 'application/x-quicktimeplayer',
            'ra' => 'audio/x-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'rar' => 'application/rar',
            'ras' => 'image/x-cmu-raster',
            'rb' => 'application/x-ruby',
            'rd' => 'chemical/x-mdl-rdfile',
            'rdf' => 'application/rdf+xml',
            'rgb' => 'image/x-rgb',
            'rhtml' => 'application/x-httpd-eruby',
            'rm' => 'audio/x-pn-realaudio',
            'rmi' => 'audio/mid',
            'roff' => 'application/x-troff',
            'ros' => 'chemical/x-rosdal',
            'rpm' => 'application/x-redhat-package-manager',
            'rss' => 'application/rss+xml',
            'rtf' => 'application/rtf',
            'rtx' => 'text/richtext',
            'rxn' => 'chemical/x-mdl-rxnfile',
            'scala' => 'text/x-scala',
            'scd' => 'application/x-msschedule',
            'scr' => 'application/x-silverlight',
            'sct' => 'text/scriptlet',
            'sd' => 'chemical/x-mdl-sdfile',
            'sd2' => 'audio/x-sd2',
            'sda' => 'application/vnd.stardivision.draw',
            'sdc' => 'application/vnd.stardivision.calc',
            'sdd' => 'application/vnd.stardivision.impress',
            'sdf' => 'chemical/x-mdl-sdfile',
            'sds' => 'application/vnd.stardivision.chart',
            'sdw' => 'application/vnd.stardivision.writer',
            'ser' => 'application/java-serialized-object',
            'setpay' => 'application/set-payment-initiation',
            'setreg' => 'application/set-registration-initiation',
            'sgf' => 'application/x-go-sgf',
            'sgl' => 'application/vnd.stardivision.writer-global',
            'sh' => 'text/x-sh',
            'shar' => 'application/x-shar',
            'shp' => 'application/x-qgis',
            'shtml' => 'text/html',
            'shx' => 'application/x-qgis',
            'sid' => 'audio/prs.sid',
            'sik' => 'application/x-trash',
            'silo' => 'model/mesh',
            'sis' => 'application/vnd.symbian.install',
            'sisx' => 'x-epoc/x-sisx-app',
            'sit' => 'application/x-stuffit',
            'sitx' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'spc' => 'chemical/x-galactic-spc',
            'spl' => 'application/x-futuresplash',
            'spx' => 'audio/ogg',
            'src' => 'application/x-wais-source',
            'sst' => 'application/vnd.ms-pkicertstore',
            'stc' => 'application/vnd.sun.xml.calc.template',
            'std' => 'application/vnd.sun.xml.draw.template',
            'sti' => 'application/vnd.sun.xml.impress.template',
            'stl' => 'application/vnd.ms-pki.stl',
            'stm' => 'text/html',
            'stw' => 'application/vnd.sun.xml.writer.template',
            'sty' => 'text/x-tex',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'sw' => 'chemical/x-swissprot',
            'swf' => 'application/x-shockwave-flash',
            'swfl' => 'application/x-shockwave-flash',
            'sxc' => 'application/vnd.sun.xml.calc',
            'sxd' => 'application/vnd.sun.xml.draw',
            'sxg' => 'application/vnd.sun.xml.writer.global',
            'sxi' => 'application/vnd.sun.xml.impress',
            'sxm' => 'application/vnd.sun.xml.math',
            'sxw' => 'application/vnd.sun.xml.writer',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'taz' => 'application/x-gtar',
            'tcl' => 'text/x-tcl',
            'tex' => 'text/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'text' => 'text/plain',
            'tgf' => 'chemical/x-mdl-tgf',
            'tgz' => 'application/x-gtar',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tk' => 'text/x-tcl',
            'tm' => 'text/texmacs',
            'torrent' => 'application/x-bittorrent',
            'tr' => 'application/x-troff',
            'trm' => 'application/x-msterminal',
            'ts' => 'text/texmacs',
            'tsp' => 'application/dsptype',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'udeb' => 'application/x-debian-package',
            'uls' => 'text/iuls',
            'ustar' => 'application/x-ustar',
            'val' => 'chemical/x-ncbi-asn1-binary',
            'vcd' => 'application/x-cdlink',
            'vcf' => 'text/x-vcard',
            'vcs' => 'text/x-vcalendar',
            'vmd' => 'chemical/x-vmd',
            'vms' => 'chemical/x-vamas-iso14976',
            'vrm' => 'x-world/x-vrml',
            'vrml' => 'x-world/x-vrml',
            'vsd' => 'application/vnd.visio',
            'wad' => 'application/x-doom',
            'wav' => 'audio/x-wav',
            'wax' => 'audio/x-ms-wax',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wcm' => 'application/vnd.ms-works',
            'wdb' => 'application/vnd.ms-works',
            'wk' => 'application/x-123',
            'wks' => 'application/vnd.ms-works',
            'wm' => 'video/x-ms-wm',
            'wma' => 'audio/x-ms-wma',
            'wmd' => 'application/x-ms-wmd',
            'wmf' => 'application/x-msmetafile',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wmv' => 'video/x-ms-wmv',
            'wmx' => 'video/x-ms-wmx',
            'wmz' => 'application/x-ms-wmz',
            'wp5' => 'application/vnd.wordperfect5.1',
            'wpd' => 'application/vnd.wordperfect',
            'wps' => 'application/vnd.ms-works',
            'wri' => 'application/x-mswrite',
            'wrl' => 'x-world/x-vrml',
            'wrz' => 'x-world/x-vrml',
            'wsc' => 'text/scriptlet',
            'wvx' => 'video/x-ms-wvx',
            'wz' => 'application/x-wingz',
            'x3d' => 'model/x3d+xml',
            'x3db' => 'model/x3d+binary',
            'x3dv' => 'model/x3d+vrml',
            'xaf' => 'x-world/x-vrml',
            'xbm' => 'image/x-xbitmap',
            'xcf' => 'application/x-xcf',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xla' => 'application/vnd.ms-excel',
            'xlb' => 'application/vnd.ms-excel',
            'xlc' => 'application/vnd.ms-excel',
            'xlm' => 'application/vnd.ms-excel',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xlt' => 'application/vnd.ms-excel',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlw' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'xof' => 'x-world/x-vrml',
            'xpi' => 'application/x-xpinstall',
            'xpm' => 'image/x-xpixmap',
            'xsd' => 'application/xml',
            'xsl' => 'application/xml',
            'xspf' => 'application/xspf+xml',
            'xtel' => 'chemical/x-xtel',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'z' => 'application/x-compress',
            'zfo' => 'application/vnd.software602.filler.form-xml-zip',
            'zip' => 'application/zip',
            'zmt' => 'chemical/x-mopac-input',
            '~' => 'application/x-trash',
            '323' => 'text/h323',
        );
        
        if ( preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix) ) {
            $ext = strtolower($fileSuffix[1]);
        } else {
            $ext = @strtolower(array_pop(explode('.',$filename)));
        }

        if ( array_key_exists($ext, $mime_types) ) 
            return $mime_types[$ext];
            
        // if ( function_exists('finfo_open') ) {
        //    $finfo = finfo_open(FILEINFO_MIME);
        //    $mimetype = finfo_file($finfo, $filename);
        //    finfo_close($finfo);
        //    return $mimetype;
        
        if (function_exists("mime_content_type"))
            $fileSuffix = @mime_content_type($filename);
            return $fileSuffix ? trim($fileSuffix[0]) : 'application/octet-stream';
        
        return 'application/octet-stream';       
    }
}