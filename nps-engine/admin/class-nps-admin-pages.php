<?php
/**
 * Gerencia a renderização das páginas do painel administrativo para o NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Admin_Pages {

    public function add_admin_menu_pages() {
        add_menu_page(
            __( 'NPS Engine', 'nps-engine' ),
            __( 'NPS Engine', 'nps-engine' ),
            'manage_options',
            'nps-engine', // Slug da página principal
            array( $this, 'render_main_admin_page' ),
            'dashicons-feedback',
            80
        );
    }

    public function enqueue_admin_styles() {
        wp_add_inline_style( 'wp-admin', '
            .nps-container { max-width: 1000px; margin-top: 20px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .nps-container h2 { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .nav-tab-wrapper { margin-bottom: 20px; }
            .nps-form-table th { width: 250px; padding-right: 20px; }
            .nps-form-table td { padding-bottom: 10px; }
            .nps-form-table input[type="text"], .nps-form-table input[type="email"], .nps-form-table input[type="number"], .nps-form-table input[type="password"], .nps-form-table select, .nps-form-table textarea { width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
            .nps-form-table textarea { min-height: 100px; }
            .nps-form-table .button-primary { margin-top: 10px; }
            .nps-contacts-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .nps-contacts-table th, .nps-contacts-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .nps-contacts-table th { background-color: #f2f2f2; }
            .nps-contacts-table .status-active { color: green; font-weight: bold; }
            .nps-contacts-table .status-inactive { color: red; font-weight: bold; }
            .nps-import-users-section { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
            .nps-import-users-section ul { list-style: none; padding: 0; }
            .nps-import-users-section li { margin-bottom: 5px; }
            .nps-messages { margin-bottom: 15px; padding: 10px; border-radius: 4px; }
            .nps-message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .nps-message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .nps-reports-filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
            .nps-reset-section { border: 2px solid #dc3232; padding: 20px; background-color: #f8d7da; margin-top: 30px;}
            .tablenav { display: flex; justify-content: space-between; align-items: center; }
            .tablenav-pages a { padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; }
            .tablenav-pages .current { background: #e0e0e0; }
        ' );
    }

    public function render_main_admin_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'contacts';
        ?>
        <div class="wrap nps-container">
            <h1><?php esc_html_e( 'NPS Engine', 'nps-engine' ); ?></h1>
            <?php if ( isset( $_GET['message'] ) ) { $message_type = ( $_GET['message_type'] == 'success' ) ? 'nps-message-success' : 'nps-message-error'; echo '<div class="nps-messages ' . esc_attr( $message_type ) . '">' . esc_html( urldecode( $_GET['message'] ) ) . '</div>'; } ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=nps-engine&tab=contacts" class="nav-tab <?php echo $active_tab == 'contacts' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Contatos', 'nps-engine' ); ?></a>
                <a href="?page=nps-engine&tab=email_settings" class="nav-tab <?php echo $active_tab == 'email_settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Layout do E-mail', 'nps-engine' ); ?></a>
                <a href="?page=nps-engine&tab=trigger_settings" class="nav-tab <?php echo $active_tab == 'trigger_settings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Gatilhos', 'nps-engine' ); ?></a>
                <a href="?page=nps-engine&tab=reports" class="nav-tab <?php echo $active_tab == 'reports' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Relatórios', 'nps-engine' ); ?></a>
                <a href="?page=nps-engine&tab=support_tools" class="nav-tab <?php echo $active_tab == 'support_tools' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Suporte e Ferramentas', 'nps-engine' ); ?></a>
            </h2>

            <?php
            switch ( $active_tab ) {
                case 'email_settings':
                    $this->render_email_settings_page();
                    break;
                case 'trigger_settings':
                    $this->render_trigger_settings_page();
                    break;
                case 'reports':
                    $this->render_reports_page();
                    break;
                case 'support_tools':
                    $this->render_support_tools_page();
                    break;
                case 'contacts':
                default:
                    $this->render_contacts_page();
                    break;
            }
            ?>
        </div>
        <?php
    }

    private function render_contacts_page() {
        global $wpdb;
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';

        // Paginação para a lista de contatos
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        $total_contacts = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name_contacts" );
        $contacts = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name_contacts ORDER BY name ASC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ), ARRAY_A );

        // Busca e Paginação para importação de usuários WP
        $search_query = isset( $_GET['wpusersearch'] ) ? sanitize_text_field( $_GET['wpusersearch'] ) : '';
        $wp_users_per_page = 10;
        $wp_users_current_page = isset( $_GET['user_paged'] ) ? max( 1, intval( $_GET['user_paged'] ) ) : 1;

        $imported_wp_user_ids = $wpdb->get_col( "SELECT wp_user_id FROM $table_name_contacts WHERE origin = 'wp_user' AND wp_user_id IS NOT NULL" );

        $user_args = array(
            'number' => $wp_users_per_page,
            'paged'  => $wp_users_current_page,
            'exclude' => $imported_wp_user_ids,
            'orderby' => 'display_name',
            'order'   => 'ASC',
        );

        if ( ! empty($search_query) ) {
            $user_args['search'] = '*' . $search_query . '*';
            $user_args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );
        }

        $wp_users_query = new WP_User_Query( $user_args );
        $wp_users = $wp_users_query->get_results();
        $total_wp_users = $wp_users_query->get_total();

        ?>
        <h2><?php esc_html_e( 'Gerenciar Contatos NPS', 'nps-engine' ); ?></h2>

        <h3><?php esc_html_e( 'Adicionar Novo Contato Manualmente', 'nps-engine' ); ?></h3>
        <form method="post" action="admin.php?page=nps-engine&tab=contacts"><?php wp_nonce_field( 'nps_add_contact_action', 'nps_add_contact_nonce' ); ?><table class="form-table nps-form-table"><tr><th scope="row"><label for="nps_contact_name"><?php esc_html_e( 'Nome', 'nps-engine' ); ?></label></th><td><input type="text" id="nps_contact_name" name="nps_contact_name" required /></td></tr><tr><th scope="row"><label for="nps_contact_email"><?php esc_html_e( 'E-mail', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_contact_email" name="nps_contact_email" required /></td></tr><tr><th scope="row"><label for="nps_contact_status"><?php esc_html_e( 'Status', 'nps-engine' ); ?></label></th><td><select id="nps_contact_status" name="nps_contact_status"><option value="1"><?php esc_html_e( 'Ativo', 'nps-engine' ); ?></option><option value="0"><?php esc_html_e( 'Inativo', 'nps-engine' ); ?></option></select></td></tr></table><p class="submit"><input type="submit" name="nps_add_contact_submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Adicionar Contato', 'nps-engine' ); ?>"></p></form>

        <div class="nps-import-users-section">
            <h3><?php esc_html_e( 'Importar Usuários WordPress', 'nps-engine' ); ?></h3>
            <p><?php esc_html_e( 'Busque e selecione os usuários do WordPress que você deseja adicionar como contatos NPS.', 'nps-engine' ); ?></p>

            <form method="get">
                <input type="hidden" name="page" value="nps-engine" />
                <input type="hidden" name="tab" value="contacts" />
                <p class="search-box">
                    <label class="screen-reader-text" for="wp-user-search-input"><?php esc_html_e( 'Buscar Usuários', 'nps-engine' ); ?>:</label>
                    <input type="search" id="wp-user-search-input" name="wpusersearch" value="<?php echo esc_attr( $search_query ); ?>" />
                    <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Buscar Usuários', 'nps-engine' ); ?>" />
                </p>
            </form>

            <form method="post" action="admin.php?page=nps-engine&tab=contacts"><?php wp_nonce_field( 'nps_import_users_action', 'nps_import_users_nonce' ); ?>
                <ul>
                <?php if ( ! empty( $wp_users ) ) : ?>
                    <?php foreach ( $wp_users as $user ) : ?>
                    <li><label><input type="checkbox" name="nps_import_users[]" value="<?php echo esc_attr( $user->ID ); ?>" /> <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)</label></li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p><?php esc_html_e('Nenhum usuário encontrado ou todos os usuários já foram importados.', 'nps-engine'); ?></p>
                <?php endif; ?>
                </ul>

                <?php if ( $total_wp_users > $wp_users_per_page ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base' => add_query_arg( array('tab' => 'contacts', 'user_paged' => '%#%') ),
                            'format' => '',
                            'prev_text' => __('&laquo;', 'nps-engine'),
                            'next_text' => __('&raquo;', 'nps-engine'),
                            'total' => ceil($total_wp_users / $wp_users_per_page),
                            'current' => $wp_users_current_page
                        ));
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $wp_users ) ) : ?>
                <p class="submit"><input type="submit" name="nps_import_users_submit" id="submit-import" class="button button-secondary" value="<?php esc_attr_e( 'Importar Selecionados', 'nps-engine' ); ?>"></p>
                <?php endif; ?>
            </form>
        </div>

        <h3><?php esc_html_e( 'Lista de Contatos', 'nps-engine' ); ?></h3>
        <table class="wp-list-table widefat fixed striped nps-contacts-table">
            <thead><tr><th><?php esc_html_e( 'Nome', 'nps-engine' ); ?></th><th><?php esc_html_e( 'E-mail', 'nps-engine' ); ?></th><th><?php esc_html_e( 'Status', 'nps-engine' ); ?></th><th><?php esc_html_e( 'Origem', 'nps-engine' ); ?></th><th><?php esc_html_e( 'Última Pesquisa', 'nps-engine' ); ?></th><th><?php esc_html_e( 'Ações', 'nps-engine' ); ?></th></tr></thead>
            <tbody>
                <?php if ( ! empty( $contacts ) ) : ?>
                    <?php foreach ( $contacts as $contact ) : ?>
                    <tr>
                        <td><?php echo esc_html( $contact['name'] ); ?></td>
                        <td><?php echo esc_html( $contact['email'] ); ?></td>
                        <td><?php $status_class = ( $contact['status'] == 1 ) ? 'status-active' : 'status-inactive'; $status_text = ( $contact['status'] == 1 ) ? __( 'Ativo', 'nps-engine' ) : __( 'Inativo', 'nps-engine' ); echo '<span class="' . esc_attr( $status_class ) . '">' . esc_html( $status_text ) . '</span>'; ?></td>
                        <td><?php echo ( $contact['origin'] == 'wp_user' ) ? esc_html__( 'Usuário WP', 'nps-engine' ) : esc_html__( 'Manual', 'nps-engine' ); ?></td>
                        <td><?php echo ( $contact['last_survey_sent'] ) ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $contact['last_survey_sent'] ) ) ) : esc_html__( 'Nunca', 'nps-engine' ); ?></td>
                        <td><?php $toggle_nonce = wp_create_nonce( 'nps_toggle_status_' . $contact['id'] ); $delete_nonce = wp_create_nonce( 'nps_delete_contact_' . $contact['id'] ); ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=nps-engine&tab=contacts&action=toggle_status&contact_id=' . $contact['id'] . '&_wpnonce=' . $toggle_nonce ) ); ?>" class="button button-small"><?php echo ( $contact['status'] == 1 ) ? esc_html__( 'Desativar', 'nps-engine' ) : esc_html__( 'Ativar', 'nps-engine' ); ?></a> <a href="<?php echo esc_url( admin_url( 'admin.php?page=nps-engine&tab=contacts&action=delete_contact&contact_id=' . $contact['id'] . '&_wpnonce=' . $delete_nonce ) ); ?>" class="button button-small button-danger" onclick="return confirm('<?php echo esc_js( __( 'Tem certeza que deseja excluir este contato e todas as suas respostas?', 'nps-engine' ) ); ?>');"><?php esc_html_e( 'Excluir', 'nps-engine' ); ?></a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'Nenhum contato encontrado.', 'nps-engine' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ( $total_contacts > $per_page ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links( array(
                    'base' => add_query_arg( array('tab' => 'contacts', 'paged' => '%#%') ),
                    'format' => '',
                    'prev_text' => __('&laquo;', 'nps-engine'),
                    'next_text' => __('&raquo;', 'nps-engine'),
                    'total' => ceil($total_contacts / $per_page),
                    'current' => $current_page
                ));
                ?>
            </div>
        </div>
        <?php endif;
    }

    private function render_email_settings_page() {
        $email_settings = get_option( 'nps_email_settings', array() ); $defaults = array( 'from_name' => get_bloginfo( 'name' ), 'from_email' => get_bloginfo( 'admin_email' ), 'reply_to_email' => '', 'email_subject' => __( 'Sua opinião é importante para nós!', 'nps-engine' ), 'email_body' => __( "Olá [contact_name],\n\nGostaríamos de saber sua opinião sobre sua recente experiência com a gente.\n\nEm uma escala de 0 a 10, o quanto você recomendaria [site_name] a um amigo ou colega?\n\n[nps_score_links]\n\nSe você tiver algum comentário adicional, por favor, sinta-se à vontade para responder a este e-mail.\n\nAgradecemos seu tempo!\n\nAtenciosamente,\nA Equipe [site_name]", 'nps-engine' ) ); $email_settings = wp_parse_args( $email_settings, $defaults );
        ?>
        <h2><?php esc_html_e( 'Configurações de Layout do Email', 'nps-engine' ); ?></h2>

        <form method="post" action="admin.php?page=nps-engine&tab=email_settings"><?php wp_nonce_field( 'nps_save_email_settings_action', 'nps_save_email_settings_nonce' ); ?>
            <h3><?php esc_html_e( 'Conteúdo do E-mail', 'nps-engine' ); ?></h3>
            <table class="form-table nps-form-table">
                <tr><th scope="row"><label for="nps_from_name"><?php esc_html_e( 'Nome do Remetente', 'nps-engine' ); ?></label></th><td><input type="text" id="nps_from_name" name="nps_from_name" value="<?php echo esc_attr( $email_settings['from_name'] ); ?>" required /></td></tr>
                <tr><th scope="row"><label for="nps_from_email"><?php esc_html_e( 'E-mail do Remetente', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_from_email" name="nps_from_email" value="<?php echo esc_attr( $email_settings['from_email'] ); ?>" required /></td></tr>
                <tr><th scope="row"><label for="nps_reply_to_email"><?php esc_html_e( 'E-mail de Resposta (Opcional)', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_reply_to_email" name="nps_reply_to_email" value="<?php echo esc_attr( $email_settings['reply_to_email'] ); ?>" /></td></tr>
                <tr><th scope="row"><label for="nps_email_subject"><?php esc_html_e( 'Assunto do E-mail', 'nps-engine' ); ?></label></th><td><input type="text" id="nps_email_subject" name="nps_email_subject" value="<?php echo esc_attr( $email_settings['email_subject'] ); ?>" required /></td></tr>
                <tr><th scope="row"><label for="nps_email_body"><?php esc_html_e( 'Corpo do E-mail', 'nps-engine' ); ?></label></th><td><textarea id="nps_email_body" name="nps_email_body" rows="10"><?php echo esc_textarea( $email_settings['email_body'] ); ?></textarea><p class="description"><?php esc_html_e( 'Use <code>[contact_name]</code>, <code>[site_name]</code>, e <code>[nps_score_links]</code>.', 'nps-engine' ); ?></p></td></tr>
            </table>
            <p class="submit"><input type="submit" name="nps_save_email_settings_submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Salvar Layout', 'nps-engine' ); ?>"></p>
        </form>
        <hr />
        <h3><?php esc_html_e( 'Testar Envio', 'nps-engine' ); ?></h3>
        <form method="post" action="admin.php?page=nps-engine&tab=email_settings"><?php wp_nonce_field( 'nps_test_email_action', 'nps_test_email_nonce' ); ?>
            <table class="form-table nps-form-table"><tr><th scope="row"><label for="nps_test_email_address"><?php esc_html_e( 'Enviar E-mail de Teste Para:', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_test_email_address" name="nps_test_email_address" required /></td></tr></table>
            <p class="submit"><input type="submit" name="nps_test_email_submit" id="submit-test" class="button button-secondary" value="<?php esc_attr_e( 'Enviar Teste', 'nps-engine' ); ?>"></p>
        </form>
        <?php
    }

    private function render_trigger_settings_page() {
        global $wpdb; $table_name_rules = $wpdb->prefix . 'nps_rules'; $rule = $wpdb->get_row( "SELECT * FROM $table_name_rules LIMIT 1", ARRAY_A ); if ( ! $rule ) { $rule = array( 'trigger_type' => 'time', 'interval_days' => 90, 'event_slug' => 'woocommerce_order_status_completed', 'delay_days' => 15 ); }
        $global_min_frequency = get_option( 'nps_global_min_frequency', 90 ); $available_events = array( 'woocommerce_order_status_completed' => __( 'Pedido WooCommerce Concluído', 'nps-engine' ) );
        ?>
        <h2><?php esc_html_e( 'Gatilhos da Pesquisa', 'nps-engine' ); ?></h2>

        <form method="post" action="admin.php?page=nps-engine&tab=trigger_settings"><?php wp_nonce_field( 'nps_save_trigger_settings_action', 'nps_save_trigger_settings_nonce' ); ?>
            <h3><?php esc_html_e( 'Política de Quarentena', 'nps-engine' ); ?></h3>
            <p><?php esc_html_e( 'Define o período mínimo que um contato deve esperar antes de poder receber uma nova pesquisa, independentemente do gatilho.', 'nps-engine' );?></p>
            <table class="form-table nps-form-table">
                <tr><th scope="row"><label for="nps_global_min_frequency"><?php esc_html_e( 'Intervalo mínimo entre pesquisas (dias):', 'nps-engine' ); ?></label></th><td><input type="number" id="nps_global_min_frequency" name="nps_global_min_frequency" value="<?php echo esc_attr( $global_min_frequency ); ?>" min="1" required /></td></tr>
            </table>
            <hr>
            <h3><?php esc_html_e( 'Configuração do Gatilho', 'nps-engine' ); ?></h3>
            <p><?php esc_html_e( 'Defina a condição que irá disparar o envio da pesquisa para os contatos elegíveis.', 'nps-engine' );?></p>
            <table class="form-table nps-form-table">
                <tr><th scope="row"><label for="nps_trigger_type"><?php esc_html_e( 'Gatilho', 'nps-engine' ); ?></label></th><td><select id="nps_trigger_type" name="nps_trigger_type"><option value="time" <?php selected( $rule['trigger_type'], 'time' ); ?>><?php esc_html_e( 'Baseado em Tempo', 'nps-engine' ); ?></option><option value="event" <?php selected( $rule['trigger_type'], 'event' ); ?>><?php esc_html_e( 'Baseado em Evento', 'nps-engine' ); ?></option></select></td></tr>
                <tr class="nps-trigger-time-field"><th scope="row"><label for="nps_interval_days"><?php esc_html_e( 'Enviar uma pesquisa a cada (dias):', 'nps-engine' ); ?></label></th><td><input type="number" id="nps_interval_days" name="nps_interval_days" value="<?php echo esc_attr( $rule['interval_days'] ); ?>" min="1" /><p class="description"><?php esc_html_e( 'A pesquisa só será enviada se o contato também estiver fora da Política de Quarentena.', 'nps-engine' ); ?></p></td></tr>
                <tr class="nps-trigger-event-field"><th scope="row"><label for="nps_event_slug"><?php esc_html_e( 'Quando o evento ocorrer:', 'nps-engine' ); ?></label></th><td><select id="nps_event_slug" name="nps_event_slug"><?php foreach ( $available_events as $slug => $label ) : ?><option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $rule['event_slug'], $slug ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><p class="description"><?php esc_html_e( 'Requer integração com o plugin correspondente (ex: WooCommerce).', 'nps-engine' ); ?></p></td></tr>
                <tr class="nps-trigger-event-field"><th scope="row"><label for="nps_delay_days"><?php esc_html_e( 'Aguardar (dias) antes de enviar a pesquisa:', 'nps-engine' ); ?></label></th><td><input type="number" id="nps_delay_days" name="nps_delay_days" value="<?php echo esc_attr( $rule['delay_days'] ); ?>" min="0" /></td></tr>
            </table>
            <p class="submit"><input type="submit" name="nps_save_trigger_settings_submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Salvar Configurações de Gatilhos', 'nps-engine' ); ?>"></p>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const triggerTypeSelect = document.getElementById('nps_trigger_type');
                if (!triggerTypeSelect) return;

                const timeFields = document.querySelectorAll('.nps-trigger-time-field');
                const eventFields = document.querySelectorAll('.nps-trigger-event-field');

                function toggleTriggerFields() {
                    const selectedType = triggerTypeSelect.value;
                    timeFields.forEach(field => { field.style.display = (selectedType === 'time') ? 'table-row' : 'none'; });
                    eventFields.forEach(field => { field.style.display = (selectedType === 'event') ? 'table-row' : 'none'; });
                }

                triggerTypeSelect.addEventListener('change', toggleTriggerFields);
                toggleTriggerFields();
            });
        </script>
        <?php
    }

    private function render_reports_page() {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : ''; $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        $reports_manager = new NPS_Reports();
        $report_data = $reports_manager->get_nps_report_data($start_date, $end_date);
        $detailed_responses = $reports_manager->get_detailed_responses($start_date, $end_date);
        ?>
        <h2><?php esc_html_e( 'Relatórios NPS', 'nps-engine' ); ?></h2>
        <form method="GET" class="nps-reports-filter-form">
            <input type="hidden" name="page" value="nps-engine">
            <input type="hidden" name="tab" value="reports">
            <label for="start_date"><?php esc_html_e('De:', 'nps-engine'); ?></label><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            <label for="end_date"><?php esc_html_e('Até:', 'nps-engine'); ?></label><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
            <button type="submit" class="button"><?php esc_html_e('Filtrar', 'nps-engine'); ?></button>
            <a href="<?php echo esc_url(add_query_arg(['action' => 'nps_export_csv', 'start_date' => $start_date, 'end_date' => $end_date, '_wpnonce' => wp_create_nonce('nps_export_csv_nonce')])); ?>" class="button button-secondary"><?php esc_html_e('Exportar para CSV', 'nps-engine'); ?></a>
        </form>
        <?php if ($report_data['total_responses'] > 0) : ?>
            <h3><?php esc_html_e( 'Visão Geral do NPS', 'nps-engine' ); ?></h3>
            <p><strong><?php esc_html_e( 'Pontuação NPS:', 'nps-engine' ); ?></strong> <?php echo esc_html( $report_data['nps_score'] ); ?></p>
            <p><strong><?php esc_html_e( 'Total de Respostas:', 'nps-engine' ); ?></strong> <?php echo esc_html( $report_data['total_responses'] ); ?></p>
            <p><strong><?php esc_html_e( 'Promotores (9-10):', 'nps-engine' ); ?></strong> <?php echo esc_html( $report_data['promoters'] ); ?> (<?php echo esc_html( round(($report_data['promoters'] / $report_data['total_responses']) * 100, 2) ); ?>%)</p>
            <p><strong><?php esc_html_e( 'Passivos (7-8):', 'nps-engine' ); ?></strong> <?php echo esc_html( $report_data['passives'] ); ?> (<?php echo esc_html( round(($report_data['passives'] / $report_data['total_responses']) * 100, 2) ); ?>%)</p>
            <p><strong><?php esc_html_e( 'Detratores (0-6):', 'nps-engine' ); ?></strong> <?php echo esc_html( $report_data['detractors'] ); ?> (<?php echo esc_html( round(($report_data['detractors'] / $report_data['total_responses']) * 100, 2) ); ?>%)</p>

            <h3><?php esc_html_e( 'Respostas Detalhadas', 'nps-engine' ); ?></h3>
            <table class="wp-list-table widefat fixed striped nps-contacts-table">
                <thead><tr><th><?php esc_html_e( 'Email do Contato', 'nps-engine' ); ?></th><th><?php esc_html_e( 'Pontuação', 'nps-engine' ); ?></th><th><?php esc_html_e( 'Data da Resposta', 'nps-engine' ); ?></th></tr></thead>
                <tbody>
                    <?php foreach ($detailed_responses as $response) : ?>
                        <tr>
                            <td><?php echo esc_html($response['contact_email']); ?></td>
                            <td><?php echo esc_html($response['score']); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $response['response_timestamp'] ) ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e( 'Nenhuma resposta encontrada para o período selecionado.', 'nps-engine' ); ?></p>
        <?php endif;
    }

    private function render_support_tools_page() {
        ?>
        <h2><?php esc_html_e( 'Suporte e Ferramentas', 'nps-engine' ); ?></h2>

        <h3><?php esc_html_e( 'Suporte Técnico', 'nps-engine' ); ?></h3>
        <p><?php esc_html_e( 'Para obter ajuda, reportar um problema ou dar sugestões, por favor, entre em contato através do e-mail:', 'nps-engine' ); ?> <a href="mailto:contato@cabeza.com.br">contato@cabeza.com.br</a>.</p>

        <h3><?php esc_html_e( 'Requisito de Envio de E-mail', 'nps-engine' ); ?></h3>
        <p><strong><?php esc_html_e( 'Importante:', 'nps-engine' ); ?></strong> <?php esc_html_e( 'O NPS Engine foca na lógica de pesquisa, mas não no envio de e-mails em si. Para garantir que os e-mails da pesquisa sejam entregues de forma confiável, é altamente recomendável que você tenha um plugin de SMTP (como WP Mail SMTP, FluentSMTP, etc.) instalado e configurado corretamente no seu site.', 'nps-engine' ); ?></p>

        <div class="nps-reset-section">
            <h3><?php esc_html_e( 'Apagar Todos os Dados do Plugin', 'nps-engine' ); ?></h3>
            <p><strong><?php esc_html_e( 'Atenção:', 'nps-engine' ); ?></strong> <?php esc_html_e( 'Esta ação é irreversível. Ela apagará permanentemente todos os contatos e respostas de pesquisa do banco de dados. Use com extremo cuidado.', 'nps-engine' ); ?></p>
            <form method="post" action="admin.php?page=nps-engine&tab=support_tools" onsubmit="return confirm('<?php echo esc_js( __( 'Você tem certeza ABSOLUTA que deseja apagar todos os contatos e respostas? Esta ação não pode ser desfeita.', 'nps-engine' ) ); ?>');">
                <?php wp_nonce_field( 'nps_reset_plugin_action', 'nps_reset_plugin_nonce' ); ?>
                <p class="submit">
                    <input type="submit" name="nps_reset_plugin_submit" class="button button-danger" value="<?php esc_attr_e( 'Apagar Todos os Dados', 'nps-engine' ); ?>">
                </p>
            </form>
        </div>
        <?php
    }
}
