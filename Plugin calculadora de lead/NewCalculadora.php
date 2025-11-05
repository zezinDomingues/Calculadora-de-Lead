<?php
/*
 * Plugin Name:        Plugin calculadora de lead (Versão Unificada)
 * Description:        Calculadora de orçamento de telhados com submissão AJAX. Todo o código está contido em um único arquivo.
 * Version:            1.2.2
 * Author:             José Domingues/2WP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Classe principal do Plugin NewCalculadora.
 */
class NewCalculadoraPlugin {

    /**
     * Construtor da classe.
     */
    public function __construct() {
        // Ação para registrar o Custom Post Type 'lead_calculadora'
        add_action('init', [$this, 'register_cpt_lead_calculadora']);

        // Adiciona o Shortcode
        add_shortcode('calculadora_lead_ajax', [$this, 'render_shortcode_with_assets']);

        // Adiciona endpoints AJAX
        add_action('wp_ajax_processar_calculo', [$this, 'processar_calculo_ajax']);
        add_action('wp_ajax_nopriv_processar_calculo', [$this, 'processar_calculo_ajax']);
    }

    /**
     * Registra o Custom Post Type (CPT) para armazenar os leads.
     */
    public function register_cpt_lead_calculadora() {
        $labels = [
            'name'                  => _x('Leads da Calculadora', 'Post Type General Name', 'pcl'),
            'singular_name'         => _x('Lead da Calculadora', 'Post Type Singular Name', 'pcl'),
            'menu_name'             => __('Leads Calculadora', 'pcl'),
            'all_items'             => __('Todos os Leads', 'pcl'),
        ];
        $args = [
            'label'                 => __('Lead da Calculadora', 'pcl'),
            'description'           => __('Leads gerados pela calculadora de orçamento', 'pcl'),
            'labels'                => $labels,
            'supports'              => ['title', 'custom-fields'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-calculator',
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
        ];
        register_post_type('lead_calculadora', $args);
    }

    /**
     * Renderiza o HTML do formulário, junto com CSS e JS inline.
     */
    public function render_shortcode_with_assets() {
        ob_start();
        ?>
        <style>
            #calculadora-container {
                background: #F3F5F8; padding: 25px; border-radius: 10px;
                max-width: 600px; margin: auto; border: 1px solid #e0e0e0;
            }
            #calculadora-container .bloco-input {
                display: flex; flex-direction: column; width: 100%;
                margin-bottom: 15px; position: relative;
            }
            #calculadora-container .bloco-input > label {
                margin-bottom: -10px; z-index: 1; background: #F3F5F8;
                padding: 0 5px; margin-left: 10px; align-self: flex-start;
                font-size: 14px; color: #555;
            }
            #calculadora-container .bloco-input > input,
            #calculadora-container .bloco-input > select {
                width: 100%; padding: 15px 12px 10px; border: 1px solid #002c4047 !important;
                border-radius: 11px !important; font-size: 16px !important; box-sizing: border-box;
            }
            #calculadora-container .box-divs { display: flex; gap: 20px; }
            #calculadora-form-ajax button[type="submit"] {
                width: 100%; padding: 15px; border-radius: 15px; background: #002c40;
                color: #fff; font-size: 18px; font-weight: bold; border: none;
                border-bottom: 3px solid #ff9a22 !important; cursor: pointer; transition: background-color 0.3s;
            }
            #calculadora-form-ajax button[type="submit"]:hover { background: #001f2e; }
            #calculadora-container .tipo-cobertura-container {
                display: flex; gap: 15px; margin-bottom: 20px;
                justify-content: space-around; flex-wrap: wrap;
            }
            #calculadora-container .tipo-cobertura-option {
                text-align: center; cursor: pointer; display: flex; flex-direction: column;
                align-items: center; font-size: 13px; line-height: 1.4; width: 100px;
            }
            #calculadora-container .tipo-cobertura-option img {
                width: 90px; height: 90px; border-radius: 50%; object-fit: cover;
                border: 3px solid #002c4030; background: #fff; transition: border-color 0.3s;
            }
            #calculadora-container .tipo-cobertura-option p { margin-top: 8px; font-weight: 500; }
            #calculadora-container .tipo-cobertura-option.active img { border-color: #ff9a22; }
            #calculadora-container .spinner {
                border: 4px solid #f3f3f3; border-radius: 50%; border-top: 4px solid #002c40;
                width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 20px auto 0;
            }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            #calculadora-resultado {
                margin-top: 20px; padding: 20px; background-color: #e9f7ef;
                border: 1px solid #4caf50; border-radius: 8px; color: #333;
            }
            #calculadora-resultado.error { background-color: #fbe9e7; border-color: #ff5722; }
            #calculadora-resultado h3 { margin-top: 0; }
            .error-message { color: #d9534f; font-size: 12px; margin-top: 5px; }
            input.error { border-color: #d9534f !important; }
        </style>
        
        <div id="calculadora-container">
            <form id="calculadora-form-ajax" method="post">
                <div class="bloco-input"><label for="nome">Nome e Sobrenome:</label><input type="text" id="nome" name="nome" required></div>
                <div class="box-divs">
                    <div class="bloco-input"><label for="email">E-mail Comercial:</label><input type="email" id="email" name="email" required></div>
                    <div class="bloco-input"><label for="empresa">Empresa:</label><input type="text" id="empresa" name="empresa" required></div>
                </div>  
                <div class="box-divs">
                    <div class="bloco-input"><label for="cnpj">CNPJ:</label><input type="text" id="cnpj" name="cnpj" required></div>
                    <div class="bloco-input"><label for="telefone">Telefone:</label><input type="tel" id="telefone" name="telefone" required></div>
                </div>
                <label>Tipo de Cobertura:</label>
                <div class="tipo-cobertura-container"></div>
                <select id="tipo-cobertura" name="tipo-cobertura" style="display:none;" required>
                    <option value="">Selecione...</option>
                    <option value="metálico">Metálico</option>
                    <option value="fibro-cimento">Fibro Cimento</option>
                    <option value="laje-concreto">Laje de Concreto</option>
                    <option value="caletao-concreto">Caletão de Concreto</option>
                </select>
                <div class="box-divs">
                    <div class="bloco-input"><label for="largura">Largura (m):</label><input type="number" id="largura" name="largura" min="0.1" step="0.01" required></div>
                    <div class="bloco-input"><label for="comprimento">Comprimento (m):</label><input type="number" id="comprimento" name="comprimento" min="0.1" step="0.01" required></div>
                </div>
                <button type="submit" id="enviar-calculo">Calcular</button>
                <div class="spinner" style="display: none;"></div>
            </form>
            <div id="calculadora-resultado" style="display: none;"></div>
        </div>

        <script>
        jQuery(function ($) {
            'use strict';
            
            const calculadora_ajax_obj = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                nonce: '<?php echo wp_create_nonce('calculadora-lead-nonce'); ?>'
            };

            const tiposDeCobertura = [
                { value: 'metálico', label: 'Metálico', img: 'https://impertelhadosindustriais.com.br/wp-content/uploads/2024/09/metalico-jpg.webp' },
                { value: 'fibro-cimento', label: 'Fibro Cimento', img: 'https://impertelhadosindustriais.com.br/wp-content/uploads/2024/09/fibro.png' },
                { value: 'laje-concreto', label: 'Laje de Concreto', img: 'https://impertelhadosindustriais.com.br/wp-content/uploads/2024/09/concreto-jpg.webp' },
                { value: 'caletao-concreto', label: 'Caletão de Concreto', img: 'https://impertelhadosindustriais.com.br/wp-content/uploads/2024/09/caletao-jpg.webp' }
            ];

            const container = $('#calculadora-container .tipo-cobertura-container');
            tiposDeCobertura.forEach(tipo => {
                container.append(`<div class="tipo-cobertura-option" data-tipo="${tipo.value}"><img src="${tipo.img}" alt="${tipo.label}"><p>${tipo.label}</p></div>`);
            });

            $('#calculadora-container').on('click', '.tipo-cobertura-option', function () {
                const tipo = $(this).data('tipo');
                $('#calculadora-container .tipo-cobertura-option').removeClass('active');
                $(this).addClass('active');
                $('#tipo-cobertura').val(tipo).trigger('change');
            });
            
            $('#cnpj').on('input', function () {
                let v = $(this).val().replace(/\D/g, '').substring(0, 14);
                v = v.replace(/^(\d{2})(\d)/, '$1.$2');
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
                v = v.replace(/(\d{4})(\d)/, '$1-$2');
                $(this).val(v);
            });
            
            $('#calculadora-form-ajax').on('submit', function (e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('#enviar-calculo');
                const spinner = form.find('.spinner');
                const resDiv = $('#calculadora-resultado');

                $('.error-message').remove();
                $('input.error').removeClass('error');
                resDiv.hide().removeClass('error');

                if (!validateForm(form)) return;

                btn.prop('disabled', true).hide();
                spinner.show();
                
                $.ajax({
                    url: calculadora_ajax_obj.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'processar_calculo',
                        nonce: calculadora_ajax_obj.nonce,
                        ...Object.fromEntries(new URLSearchParams(form.serialize()))
                    },
                    success: function (response) {
                        if (response.success) {
                            const data = response.data;
                            const resultHTML = `<h3>Obrigado!</h3><p>Seu orçamento foi calculado e enviado para seu e-mail.</p>
                                <ul>
                                    <li><strong>Área Total:</strong> ${data.area.toFixed(2).replace('.', ',')} m²</li>
                                </ul>`;
                            resDiv.html(resultHTML).show();
                            form[0].reset();
                            $('#calculadora-container .tipo-cobertura-option').removeClass('active');
                        } else {
                            if (response.data.errors) {
                                Object.keys(response.data.errors).forEach(key => showFieldError($(`#${key}`), response.data.errors[key]));
                            } else {
                                resDiv.html(`<p>${response.data.message || 'Ocorreu um erro.'}</p>`).addClass('error').show();
                            }
                        }
                    },
                    error: function () {
                        resDiv.html('<p>Erro de comunicação. Tente novamente.</p>').addClass('error').show();
                    },
                    complete: function () {
                        spinner.hide();
                        btn.prop('disabled', false).show();
                    }
                });
            });

            // NOVA FUNÇÃO PARA VALIDAR CNPJ
            function validaCNPJ(cnpj) {
                cnpj = cnpj.replace(/[^\d]+/g,'');
                if(cnpj == '' || cnpj.length != 14) return false;
                if (/^(\d)\1+$/.test(cnpj)) return false;

                let tamanho = cnpj.length - 2
                let numeros = cnpj.substring(0,tamanho);
                let digitos = cnpj.substring(tamanho);
                let soma = 0;
                let pos = tamanho - 7;
                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }
                let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                if (resultado != digitos.charAt(0)) return false;
                    
                tamanho = tamanho + 1;
                numeros = cnpj.substring(0,tamanho);
                soma = 0;
                pos = tamanho - 7;
                for (let i = tamanho; i >= 1; i--) {
                    soma += numeros.charAt(tamanho - i) * pos--;
                    if (pos < 2) pos = 9;
                }
                resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
                if (resultado != digitos.charAt(1)) return false;
                        
                return true;
            }
            
            function validateForm(form) {
                let isValid = true;
                form.find('input[required], select[required]').each(function() {
                    if (!$(this).val()) { isValid = false; showFieldError($(this), 'Campo obrigatório.'); }
                });

                // Validação de E-mail
                const emailField = $('#email');
                if (emailField.val() && ["@gmail", "@hotmail", "@outlook", "@yahoo", "@bol"].some(d => emailField.val().includes(d))) {
                    isValid = false;
                    showFieldError(emailField, 'Use um e-mail corporativo.');
                    emailField.val('').focus();
                }

                // VALIDAÇÃO DE CNPJ ADICIONADA AQUI
                const cnpjField = $('#cnpj');
                if (cnpjField.val() && !validaCNPJ(cnpjField.val())) {
                    isValid = false;
                    showFieldError(cnpjField, 'CNPJ inválido.');
                    cnpjField.val('').focus();
                }

                return isValid;
            }
            
            function showFieldError(field, msg) { clearFieldError(field); field.addClass('error').after(`<span class="error-message">${msg}</span>`); }
            function clearFieldError(field) { field.removeClass('error').next('.error-message').remove(); }
            
            $('#calculadora-form-ajax').on('input change', 'input, select', function() { clearFieldError($(this)); });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Processa a submissão AJAX do formulário.
     */
    public function processar_calculo_ajax() {
        check_ajax_referer('calculadora-lead-nonce', 'nonce');

        $errors = []; $data = [];
        $fields = ['nome', 'email', 'empresa', 'cnpj', 'telefone', 'tipo-cobertura', 'largura', 'comprimento'];
        
        foreach ($fields as $field) {
            if (empty($_POST[$field])) { $errors[$field] = 'Campo obrigatório.'; continue; }
            
            if ($field === 'email') {
                $data[$field] = sanitize_email($_POST[$field]);
                if (!is_email($data[$field])) {
                    $errors[$field] = 'E-mail inválido.';
                } else {
                    $dominios_proibidos = ['@gmail', '@hotmail', '@outlook', '@yahoo', '@bol', '@uol', '@live', '@msn', '@icloud'];
                    foreach ($dominios_proibidos as $dominio) {
                        if (strpos($data[$field], $dominio) !== false) {
                            $errors[$field] = 'Por favor, utilize um e-mail corporativo.';
                            break; 
                        }
                    }
                }
            } elseif ($field === 'cnpj') { // VALIDAÇÃO PHP DO CNPJ
                $data[$field] = sanitize_text_field($_POST[$field]);
                if (!$this->valida_cnpj_php($data[$field])) {
                    $errors[$field] = 'CNPJ inválido.';
                }
            } elseif (in_array($field, ['largura', 'comprimento'])) {
                $data[$field] = floatval(str_replace(',', '.', $_POST[$field]));
                if ($data[$field] <= 0) $errors[$field] = 'O valor deve ser maior que zero.';
            } else {
                $data[$field] = sanitize_text_field($_POST[$field]);
            }
        }
        
        if (!empty($errors)) { wp_send_json_error(['errors' => $errors]); }

        $acf_keys = ['metálico' => 'metalico', 'fibro-cimento' => 'fibro', 'laje-concreto' => 'laje', 'caletao-concreto' => 'caletao'];
        $acf_key = $acf_keys[$data['tipo-cobertura']] ?? '';
        $grupo = function_exists('get_field') ? get_field($acf_key, 'options') : null;

        if (!$grupo || empty($grupo['consumo_em_m']) || empty($grupo['valor_do_material_em_m'])) {
            wp_send_json_error(['message' => 'Configuração para este tipo de cobertura não encontrada.']);
        }
        $consumo_m2 = (float) $grupo['consumo_em_m'];
        if ($consumo_m2 <= 0) { wp_send_json_error(['message' => 'Configuração de consumo inválida.']); }
        
        $valor_material_m2 = (float) $grupo['valor_do_material_em_m'];
        $mao_de_obra = 80;
        $area = $data['largura'] * $data['comprimento'];
        $total_unitario  = (($valor_material_m2 / $consumo_m2) * 1.2) * 1.2 + $mao_de_obra;
        $total_final = $area * $total_unitario;

        $result_data = array_merge($data, [
            'area' => $area, 'consumo_m2' => $consumo_m2, 'valor_material_m2' => $valor_material_m2,
            'mao_de_obra' => $mao_de_obra, 'total_unitario' => $total_unitario, 'total_final' => $total_final,
            'data_hora' => current_time('mysql'),
        ]);

        $this->save_lead($result_data);
        $this->send_support_email($result_data);
        $this->enviar_email_calculadora($data['email'], $data['nome'], $total_unitario, $total_final); // Corrigido para passar total_unitario
        // Removi a chamada duplicada para send_client_email, pois enviar_email_calculadora já faz isso.
        
        wp_send_json_success($result_data);
    }

    /**
     * Função PHP para validar o CNPJ (backend).
     */
    private function valida_cnpj_php($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        if (strlen($cnpj) != 14) return false;
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;

        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;
        
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
    
    private function save_lead($data) {
        $post_id = wp_insert_post([
            'post_title'   => "Lead de {$data['nome']} - " . date_i18n('d/m/Y H:i', strtotime($data['data_hora'])),
            'post_type'    => 'lead_calculadora',
            'post_status'  => 'publish',
        ]);
        if ($post_id && !is_wp_error($post_id)) {
            foreach ($data as $key => $value) { update_post_meta($post_id, 'pcl_' . $key, $value); }
        }
    }

    private function send_support_email($data) {
        $to = 'suporte@2wp.com.br';
        $subject = 'Novo Lead da Calculadora de Telhados';
        $body = "<h2>Novo Lead Recebido</h2><ul>";
        foreach ($data as $key => $value) { $body .= "<li><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> " . esc_html($value) . "</li>"; }
        $body .= "</ul>";
        wp_mail($to, $subject, $body, ['Content-Type: text/html; charset=UTF-8']);
    }

    // A função send_client_email foi removida para evitar duplicidade, pois enviar_email_calculadora já envia o e-mail ao cliente.

    public function enviar_email_calculadora($email, $nome, $valor_aplicado_m2, $valor_total_aplicado) {
        $logo_url = 'https://impertelhadosindustriais.com.br/wp-content/uploads/2021/12/logo-impercoat-telhados-industriais-1024x428.png';
        $contato = 'Contato: enduris@impercoat.com.br - WhatsApp: 11 97607-9277';
        $subject = 'Proposta Sistema Enduris para Recuperação de Telhados';

        $message = '
        <html>
        <head>
            <style>
                body { background-color: #f0f0f0; font-family: Arial, sans-serif; }
                .email-container { background-color: #ffffff; width: 100%; max-width: 600px; margin: 0 auto; padding: 0; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                .email-header { text-align: center; margin-bottom: 30px; }
                .email-header img { max-width: 100%; height: auto; }
                .email-body { text-align: center; color: #333333; padding:20px; }
                .email-body h2 { font-size: 18px; margin-bottom: 20px; }
                .email-body p { font-size: 16px; margin: 10px 0; }
                .email-footer { font-size: 14px; color: #666666; text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dddddd; }
                .price-box { font-size: 18px; font-weight: bold; margin: 20px 0; color: #0073aa; }
            </style>
        </head>
        <body style="background:#E4E4E4;">
            <div class="email-container" >
                <div class="email-header" style="background: #0A1E2F; padding:0;">
                    <img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/unnamed.png">     
                </div>
                <img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/unnamed.webp" style="max-width:100%;">
                <div class="email-body" style="text-align:left;">
                    <p><strong>'.esc_html($nome).',</strong></p>
                    <p>Obrigado pelo seu interesse no sistema Enduris para recuperação de telhados monolíticos, industriais e comerciais.</p>
                    <p>Para 10 anos de garantia, esse é o valor de referência da membrana de silicone aplicada para recuperação do seu telhado considerando a contratação de um aplicador credenciado e os dados de entrada fornecidos.</p>
                    <ul>
                        <li><strong>Valor por m² – R$ '.number_format($valor_aplicado_m2, 2, ',', '.').' </strong></li>
                        <li><strong>Valor total do projeto – R$ '.number_format($valor_total_aplicado, 2, ',', '.') .'</strong></li>
                    </ul>
                    <p>O sistema Enduris para recuperação e impermeabilização de telhados é indicado para telhados industriais ou comerciais, metálicos ou de concreto.</p>
                    <img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/unnamed-1.png" style="max-width:100%; margin-right:auto; margin-left:auto; display:block;">
                    <p><strong>Veja outros exemplos de aplicação de sucesso com o sistema Enduris.</strong></p>
                    <div style="text-align:center;">
                        <p style="padding:15px; background:#0A1E2F; color:#fff; margin: 10px auto; display:block; width:220px; text-align:center;"><a href="https://u34229387.ct.sendgrid.net/ls/click?upn=u001.9-2BeP5spZWpWJCle0tNgUH2u6TSXJ-2BzQvP6q3ethzGygoE6P-2FEvZmuV8Zp-2Byzy-2BVTw1D24WZ6zRoAEXq35JetJiy59xewB6fC5ZEWxvxhfmeP6-2BYIhXDo4RslwQXotSDmEmOrTUsRFVioqWBPHB8hCdUk08alDSF29ZKNAtMBS6fw-2F40hwutWPCr-2BOB48woIevLvqQX-2BktOOndabRBP-2FJijYogfPvmhbrn-2BxSEuIzMdaE0wpb60qJsd7gMbo5iEVlX6M7_9x7Z2VAWZLm5OLMTRRPIa1O3-2Fxpui8o66Fii0617a2JyiAx-2Fmdb6zcMmmXdadOwdZzrXoRgXtlWL-2B2xZVtZopRvWN0II-2FiZMpjhVJ5YwcAZPQAJCwxplLB-2FSE0b6jrkSyKQc5SnejkRS6MCzG8WOCHjB0TmG7qYAIHF0RQmND7AlVHTgC0lcOisID36cad6TEZbfd65XfqC7bVLS0JEAcME-2BSOck6kOusluhXr0Vqusq2IlMGU8bZCj-2BizxmTG9HGvwDzjQlfBkgz9IQQaQlBT1C6wwI1BiqQmCAaO20glaiF-2FokmVJMZpPoX3xU5us-2F" style="color:#fff; text-decoration:none;">Garantias de até 20 anos</a></p>
                        <p style="padding:15px; background:#0A1E2F; color:#fff; margin: 10px auto; display:block; width:220px; text-align:center;"><a href="https://u34229387.ct.sendgrid.net/ls/click?upn=u001.9-2BeP5spZWpWJCle0tNgUH2u6TSXJ-2BzQvP6q3ethzGygoE6P-2FEvZmuV8Zp-2Byzy-2BVTJkpkVLDAg6aOc2OTiKo8cw4BO63BdsB7dTqfH3CAexxOhMYR2x81eWQg6Pl3lgkQwXUHRUBdG5twqyH1FhCf4JT05-2BOAH0MgrsgEnDSUV5ZMZrMn1TSD1q8qvNgOXeAULaH9_9x7Z2VAWZLm5OLMTRRPIa1O3-2Fxpui8o66Fii0617a2JyiAx-2Fmdb6zcMmmXdadOwdZzrXoRgXtlWL-2B2xZVtZopRvWN0II-2FiZMpjhVJ5YwcAZPQAJCwxplLB-2FSE0b6jrkSyKQc5SnejkRS6MCzG8WOCHjB0TmG7qYAIHF0RQmND7ACtYTFNLIvwHWYBAC6sdAtIJTvk2EQnpf5xyCJe-2BZK-2Fzn5PtwZvfkSS0AB6PiYgm-2F8-2BNxUY1Gp2lBTuONfIWPMZsHgGFHIn73goK26-2Bk8wO71lHxhxh10ZNLXN8PpxPt0eopozASfQMuqYG5Q6REJe" style="color:#fff; text-decoration:none;">Casos de aplicação no Brasil</a></p>
                        <p style="padding:15px; background:#0A1E2F; color:#fff; margin: 10px auto; display:block; width:220px; text-align:center;"><a href="https://u34229387.ct.sendgrid.net/ls/click?upn=u001.9-2BeP5spZWpWJCle0tNgUH2u6TSXJ-2BzQvP6q3ethzGygoE6P-2FEvZmuV8Zp-2Byzy-2BVTB4-2BbKY7f7kDVOdoZIMFRl9ozMJ7rhKy-2BjB0LjBRlYHod3agVDraem6LFvUGoU2eYGlJUGfhZR9-2F7KcKalK658sMwd0YxVserGNt5RQVTr2CkgIX0tEjUaFGQR8o38fZMyx4AH3qvfk5IGOJWlWfozH-2FzfhcMHBcXla6fm-2B23yU9X9NqqLdiFxCxjvkSRAIITyTnW_9x7Z2VAWZLm5OLMTRRPIa1O3-2Fxpui8o66Fii0617a2JyiAx-2Fmdb6zcMmmXdadOwdZzrXoRgXtlWL-2B2xZVtZopRvWN0II-2FiZMpjhVJ5YwcAZPQAJCwxplLB-2FSE0b6jrkSyKQc5SnejkRS6MCzG8WOCHjB0TmG7qYAIHF0RQmND7DJ-2F7iiddn3p8jTT6g4x2xVxW1KC7MJKAMI95FWm7xMnE0N61xNPoeGw8JI0pCb7E9a0qI2gLxQapCcZvNsnruojFCGu9GZLQhVSKkNmw5jg5Ph8HGjxevvtDDLvwqIIiKgdT5ydKa7VLbdi-2Bc9rxs6" style="color:#fff; text-decoration:none;">Recuperação de Coberturas Metálicas</a></p>
                    </div>
                </div>
                <div class="email-footer">
                    <p>Esta proposta é baseada nos dados compartilhados apenas para referência de preço. Para uma proposta oficial detalhada e específica para o seu projeto por favor entre em contato com o nosso time técnico.</p>
                    <p>Contato: enduris@impercoat.com.br | WhatsApp: 11 97607-9277</p>
                </div>
                <img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/siga-nos.png" style="width:100%">
                <div class="redes" style="display: flex; background: #dddddd;">
                    <a href="https://impertelhadosindustriais.com.br/"><img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/web.png" style="width:100%"></a>
                    <a href="https://www.youtube.com/channel/UC4TAkGv2UEpBo7MGCDnYUuA"><img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/youtube.png" style="width:100%"></a>
                    <a href="https://api.whatsapp.com/send/?phone=%2B5511976079277&text=Quero+saber+mais+sobre+impermeabiliza%C3%A7%C3%A3o&type=phone_number&app_absent=0"><img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/whatsapp.png" style="width:100%"></a>
                    <a href="https://www.linkedin.com/in/impercoat-impermeabiliza%C3%A7%C3%A3o-de-telhados-4862271b5/"><img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/linkedin.png" style="width:100%"></a>
                    <a href="https://www.instagram.com/impercoat_/"><img src="https://impertelhadosindustriais.com.br/wp-content/uploads/2024/12/instagram.png" style="width:100%"></a>
                </div>
            </div>
        </body>
        </html>
        ';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($email, $subject, $message, $headers);
    }

}

// Inicializa o plugin
new NewCalculadoraPlugin();