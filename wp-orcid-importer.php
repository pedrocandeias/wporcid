<?php
/**
 * Plugin Name: Wordpress Orcid Importer
 * Plugin URI: https://www.pedrocandeias.net
 * Description: Display ORCID Profile content using a shortcode to insert in a page or post
 * Version: 1.0.1
 * Author: Pedro Candeias
 * Author URI: https://www.pedrocandeias.net
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wporicimporter
 * Domain Path: /languages
 */

 function wp_orcid_importer_init() {
   load_plugin_textdomain( 'wporicimporter', false, 'wp-orcid-importer/languages' );
 }
 add_action('init', 'wp_orcid_importer_init');

function wp_orcid_importer_code($atts)
{
    $default = array(
       'orcidid' => '#',
       'publimit' => '3',
   );
    $args = shortcode_atts($default, $atts);

    $orcidIDstring = $args['orcidid'];
    $orcidID = array( $orcidIDstring );
    $pubLimit = $args['publimit'];
    $ordem = 'pessoa';


    function cURL($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
        curl_close($ch);
    }


    function fetch($orcidID, $elementID, $pubLimit, $ordem)
    {
        if ($elementID == 'publica') {
            $profileContent = [];
            foreach ($orcidID as $id) {
                $url = 'https://pub.orcid.org/v2.0/' . $id . '/works';
                $out = cURL($url);
                array_push($profileContent, $out);
            }
            $sliceProfile = 2;
        } else {
            foreach ($orcidID as $id) {
                $url = 'https://pub.orcid.org/v2.0/' . $id . '/person';
                $output = cURL($url);
            }
            $sliceProfile = 1;
        }


        $z = -1;
        $arrayPublica = array();

        foreach ($profileContent as $output) {
            $z++;

            $jsonInput = json_decode($output, true);

            if ($sliceProfile == 1) {

            // Biografia
                if ($elementID == 'bio') {
                    echo '<h2>'.__('Biography', 'wporicimporter').'</h2>';
                    echo $jsonInput['biography']['content'];
                }
            } else {
                $pubIDs = [];
                $displayIndexs = [];
                $y = 0;
                foreach ($jsonInput['group'] as $nivel1) {
                    foreach ($nivel1['work-summary'] as $nivel2) {
                        if ($y==$pubLimit) {
                            break;
                        }
                        if($nivel2['display-index'] == '0'){}else{
                        array_push($pubIDs, $nivel2['put-code']);
                        }
                        $y++;
                    }
                }

                // Chamar a função para obter Publicações
                $dadosJournal = fetchPubArray($orcidID[$z], $pubIDs, $pubLimit);
                foreach ($dadosJournal as $nivel1) {
                    array_push($arrayPublica, $nivel1);
                }
            }
        }
        // Chamar a função para escrever HTML
        escreve($arrayPublica, $ordem);
    }


    function fetchPubArray(&$orcidID, &$pubIDs, &$pubLimit) {
        $array = array();
        $i = 0;
        $varNum = 1;
        foreach ($pubIDs as $pubID) {
            $i++;
            if ($i == 1) {
                ${'array' . $varNum} = [];
            } elseif ($i == 50) {
                $varNum++;
                ${'array' . $varNum} = [];
                $i = 0;
            }
            array_push(${'array' . $varNum}, $pubID);
        }

    



        $out = cURL('https://pub.orcid.org/v2.0/' . $orcidID . '/person');
        $jsonInputNome = json_decode($out, true);

        if (!is_null($jsonInputNome['name']['credit-name'])) {
            $pessoa = $jsonInputNome['name']['credit-name']['value'];
        } else {
            $nome1 = $jsonInputNome['name']['given-names']['value'];
            $nome2 = $jsonInputNome['name']['family-name']['value'];
            $pessoa = $nome1 . ' ' . $nome2;
        }



        for ($i=1; $i <= $varNum ; $i++) {
            $str = implode(",", ${'array' . $i});
            $urlPubName = 'https://pub.orcid.org/v2.0/' . $orcidID . '/works/' . $str;
            $output = cURL($urlPubName);

            $jsonInput = json_decode($output, true);


            foreach ($jsonInput['bulk'] as $nivel1) {
                ${'array' . $i} = array(
                    'pubid' => '',
                    'id' => '',
                    'titulo' => '',
                    'journal' => '',
                    'data' => '',
                    'tipo' => '',
                    'url' => '',
                    'source-name' =>'',
                );

                ${'array' . $i}['pubid'] = $varNum;
                ${'array' . $i}['id'] = $pessoa;

                foreach ($nivel1['work']['title']['title'] as $tituloPub) {
                    $journalFetchTitulo = $tituloPub;
                    ${'array' . $i}['titulo'] = $journalFetchTitulo;
                }

                if (!is_null($nivel1['work']['journal-title'])) {
                    foreach ($nivel1['work']['journal-title'] as $tituloJour) {
                        $journalFetchNome = $tituloJour;
                        ${'array' . $i}['journal'] = $journalFetchNome;
                    }
                } else {
                }

                if (!is_null($nivel1['work']['publication-date'])) {
                    if (!is_null($nivel1['work']['publication-date']['year'])) {
                        foreach ($nivel1['work']['publication-date']['year'] as $pubData) {
                            $journalFetchData = $pubData;
                            ${'array' . $i}['data'] = $journalFetchData;
                        }
                    } else {
                    }
                } else {
                }

                $journalFetchTipo = $nivel1['work']['type'];
                $journalTipoUnder = str_replace('_', ' ', $journalFetchTipo);
                $journalTipoCapital = ucwords(strtolower($journalTipoUnder));
                ${'array' . $i}['tipo'] = $journalTipoCapital;


                if (!is_null($nivel1['work']['url'])) {
                    foreach ($nivel1['work']['url'] as $pubURL) {
                        $journalFetchURL = $pubURL;
                        ${'array' . $i}['url'] = $journalFetchURL;
                    }
                } else {

                }
                array_push($array, ${'array' . $i});
            }
            return $array;
        }
    }


    function escreve($arrayPublica, $ordem)
    {
        echo '<meta charset="UTF-8">';

        if ($ordem == 'data') {
            uasort($arrayPublica, 'cmpData');
        } elseif ($ordem == 'tipo') {
            uasort($arrayPublica, 'cmpTipo');
        } elseif ($ordem == 'pessoa') {
            uasort($arrayPublica, 'cmpPessoa');
        }

        foreach ($arrayPublica as $nivel1) {
           
            if (isset($nivel1['pubid'])) {
                $journalpubid = $nivel1['pubid'];
            }
            if (isset($nivel1['titulo'])) {
                $titulo = $nivel1['titulo'];
            } else {
                continue;
            }
            if (isset($nivel1['journal'])) {
                $journal = $nivel1['journal'];
            }
            if (isset($nivel1['id'])) {
                $pessoa = $nivel1['id'];
            }
            if (isset($nivel1['data'])) {
                $data = $nivel1['data'];
            }
            if (isset($nivel1['tipo'])) {
                $tipo = $nivel1['tipo'];
            }
            if (isset($nivel1['url'])) {
                $url = $nivel1['url'];
            }



            echo '<div class="publication">';
            /* if($pessoa !== ''){echo '<p class="publication-author">' . $pessoa . '</p>';} */
            if($titulo !== ''){echo '<p class="publication-title"><strong>' . $titulo . '</strong></p>';}
            if($journal !== ''){echo '<p class="publication-journal">' . $journal . '</p>';}
            if($data !== ''){echo '<p class="publication-date"><strong>'.__('Publication date:', 'wporicimporter').'</strong> ' . $data . '</p>';}
            if($tipo !== ''){echo '<p class="publication-type"><strong>'.__('Type of publication:', 'wporicimporter').'</strong> '. $tipo . '</p>';}
            if($url !== ''){echo '<p class="publication-url"><strong>URL: </strong><a href="' . $url . '">' . $url . '</a>';}
            echo '</div>';
        }
    }


    // Ordenações
    // Data de Publicação
    function cmpData($a, $b)
    {
        if ($a['data'] < $b['data']) {
            return 1;
        } elseif ($a['data'] > $b['data']) {
            return -1;
        } else {
            return 0;
        }
    }

    // Tipo de Publicação > Data
    function cmpTipo($a, $b)
    {
        if ($a['tipo'] < $b['tipo']) {
            return -1;
        } elseif ($a['tipo'] > $b['tipo']) {
            return 1;
        } else {
            if ($a['data'] < $b['data']) {
                return 1;
            } elseif ($a['data'] > $b['data']) {
                return -1;
            } else {
                return 0;
            };
        }
    }

    // Pessoa
    function cmpPessoa($a, $b)
    {
        if ($a['id'] < $b['id']) {
            return -1;
        } elseif ($a['id'] > $b['id']) {
            return 1;
        } else {
            if ($a['data'] < $b['data']) {
                return 1;
            } elseif ($a['data'] > $b['data']) {
                return -1;
            } else {
                return 0;
            };
        }
    }
    ob_start();
    fetch($orcidID, 'publica', $pubLimit, $ordem);
  $ReturnString = ob_get_contents();
  ob_end_clean();
  return $ReturnString;
}


add_shortcode('wp-orcid-importer', 'wp_orcid_importer_code');
