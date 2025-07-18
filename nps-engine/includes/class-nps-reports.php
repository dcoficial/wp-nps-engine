<?php
/**
 * Gerencia a geração e exibição de relatórios NPS para o plugin NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Reports {

    /**
     * Retorna os dados de relatório NPS, com filtro de data opcional.
     *
     * @param string $start_date Data de início no formato Y-m-d.
     * @param string $end_date Data de fim no formato Y-m-d.
     * @return array Dados do relatório.
     */
    public function get_nps_report_data($start_date = '', $end_date = '') {
        global $wpdb;
        $table_name_responses = $wpdb->prefix . 'nps_survey_instances';

        // CORREÇÃO: A query base agora inclui placeholders para os nomes das tabelas para maior segurança.
        // E o placeholder para 'responded' está fixo na query.
        $query = "SELECT score FROM {$table_name_responses} WHERE responded = 1";
        $params = array();

        if ( !empty($start_date) ) {
            $query .= " AND response_timestamp >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        if ( !empty($end_date) ) {
            $query .= " AND response_timestamp <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        // CORREÇÃO: Só executa wpdb::prepare se houver parâmetros (filtros de data).
        if ( !empty($params) ) {
            $responses = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
        } else {
            $responses = $wpdb->get_results( $query, ARRAY_A );
        }

        $total_responses = count($responses);
        $promoters = 0;
        $passives = 0;
        $detractors = 0;

        foreach ($responses as $response) {
            if ($response['score'] >= 9) { $promoters++; } 
            elseif ($response['score'] >= 7) { $passives++; } 
            else { $detractors++; }
        }

        $nps_score = 0;
        if ($total_responses > 0) {
            $nps_score = (($promoters - $detractors) / $total_responses) * 100;
        }

        return array(
            'total_responses' => $total_responses,
            'promoters'       => $promoters,
            'passives'        => $passives,
            'detractors'      => $detractors,
            'nps_score'       => round($nps_score, 2),
        );
    }

    /**
     * Retorna as respostas detalhadas da pesquisa, com filtro de data opcional.
     *
     * @param string $start_date Data de início no formato Y-m-d.
     * @param string $end_date Data de fim no formato Y-m-d.
     * @return array Lista de respostas detalhadas.
     */
    public function get_detailed_responses($start_date = '', $end_date = '') {
        global $wpdb;
        $table_name_responses = $wpdb->prefix . 'nps_survey_instances';
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';

        $query = "SELECT inst.score, inst.response_timestamp, cont.email AS contact_email
                  FROM {$table_name_responses} AS inst
                  JOIN {$table_name_contacts} AS cont ON inst.contact_id = cont.id
                  WHERE inst.responded = 1";
        
        $params = array();

        if ( !empty($start_date) ) {
            $query .= " AND inst.response_timestamp >= %s";
            $params[] = $start_date . ' 00:00:00';
        }
        if ( !empty($end_date) ) {
            $query .= " AND inst.response_timestamp <= %s";
            $params[] = $end_date . ' 23:59:59';
        }
        
        $query .= " ORDER BY inst.response_timestamp DESC";

        // CORREÇÃO: Só executa wpdb::prepare se houver parâmetros (filtros de data).
        if ( !empty($params) ) {
            return $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );
        } else {
            return $wpdb->get_results( $query, ARRAY_A );
        }
    }

    /**
     * Exporta as respostas detalhadas para um arquivo CSV.
     */
    public function export_responses_to_csv() {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

        $responses = $this->get_detailed_responses($start_date, $end_date);

        $filename = 'nps_responses_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        // Cabeçalho do CSV
        fputcsv($output, array('Email do Contato', 'Pontuacao', 'Data da Resposta'));

        // Linhas do CSV
        if ( !empty($responses) ) {
            foreach ($responses as $response) {
                fputcsv($output, array(
                    $response['contact_email'],
                    $response['score'],
                    $response['response_timestamp'],
                ));
            }
        }

        fclose($output);
        exit;
    }
}