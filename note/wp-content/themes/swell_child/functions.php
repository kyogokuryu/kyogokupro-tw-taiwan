<?php

/* 子テーマのfunctions.phpは、親テーマのfunctions.phpより先に読み込まれることに注意してください。 */


/**
 * 親テーマのfunctions.phpのあとで読み込みたいコードはこの中に。
 */
// add_filter('after_setup_theme', function(){
// }, 11);


/**
 * 子テーマでのファイルの読み込み
 */
add_action('wp_enqueue_scripts', function() {
	$timestamp = date( 'Ymdgis', filemtime( get_stylesheet_directory() . '/style.css' ) );
	wp_enqueue_style( 'child_style', get_stylesheet_directory_uri() .'/style.css', [], $timestamp );
}, 11);

//titleタグ出力
add_theme_support( 'title-tag' );

//アイキャッチ設定
add_theme_support('post-thumbnails');

//アドミンバーを非表示
add_filter( 'show_admin_bar', '__return_false' );

//ログイン時のフロントの編集リンクを非表示
add_filter( 'edit_post_link', '__return_false');

//記事内でサイトURL、テンプレートパス取得
function shortcode_theme_path() {
    return get_stylesheet_directory_uri();
}
function shortcode_site_url() {
    return home_url();
}
add_shortcode('theme_path', 'shortcode_theme_path');
add_shortcode('site_url', 'shortcode_site_url');

function add_query_vars_filter( $vars ){
    $vars[] = "source";
    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

//固定ページ・カスタム投稿タイプのスラッグをbodyのクラス名に追加
function my_body_class($classes) {
    if (is_page()) {
        $page = get_post();
        $classes[] = $page->post_name;
    }else{
        if(is_tax()){
            $taxonomy = get_query_var('taxonomy');
            $post_type = get_taxonomy( $taxonomy )->object_type[0];
        }else{
            $post_type = get_query_var('post_type');
        }
        if(!$post_type == ''){
            $classes[] = $post_type;
            $m_key = array_search('home', $classes);
            unset($classes[${'m_key'}]);
        }
    }
    return $classes;
}
add_filter('body_class', 'my_body_class');

//記事のスラッグを自動で生成(日本語禁止)
function auto_post_slug( $slug, $post_ID, $post_status, $post_type ) {
if ( preg_match( '/(%[0-9a-f]{2})+/', $slug ) ) {
$slug = utf8_uri_encode( $post_type ) . '-' . $post_ID;
}
return $slug;
}
add_filter( 'wp_unique_post_slug', 'auto_post_slug', 10, 4 );

//パンくず
function breadcrumb(){
    global $post;
    $str ='';
    $separate = '';
    if(!is_home()&&!is_admin()){
        $post_type = get_post_type_object(get_post_type());
        $post_type_name = $post_type->labels->singular_name;
        $post_type_slug = $post_type->name;

        $str.= '<div id="breadcrumb"><ul>';
        $str.= '<li class="home"><a href="'. home_url() .'">home</a>'.$separate.'</li>';

        if($post_type_slug != 'post' && $post_type_slug != 'page' && !is_post_type_archive()){
          $str.= '<li><a href="'. home_url() .'/'.$post_type_slug.'">'.$post_type_name.'</a>'.$separate.'</li>';
        }
        //カテゴリー
        if(is_category()) {
            $cat = get_queried_object();
            if($cat -> parent != 0){
                $ancestors = array_reverse(get_ancestors( $cat -> cat_ID, 'category' ));
                foreach($ancestors as $ancestor){
                    $str.='<li><a href="'. get_category_link($ancestor) .'">'. get_cat_name($ancestor) .'</a>'.$separate.'</li>';
                }
            }
            $str.='<li><span>'. $cat->cat_name . '</span></li>';
        }
        //カスタム投稿タイプ
        elseif (is_post_type_archive()) {
            $str.= '<li><span>'.$post_type_name.'</span></li>';
        }
        //タクソノミー
        elseif(is_tax()) {
            $post_type = get_post_type_object(get_post_type());
            $tax = get_queried_object();
            if($tax -> parent != 0){
                $ancestors = array_reverse(get_ancestors( $tax->term_id, $tax->taxonomy ));
                foreach($ancestors as $ancestor){
                    $term = get_term($ancestor, $tax->taxonomy);
                    $str.='<li><a href="'. get_term_link($ancestor) .'">'. $term->name .'</a>'.$separate.'</li>';
                }
            }
            $str.='<li><span>'. $tax->name . '</span></li>';
        }
        //月別アーカイブ
        elseif(is_month()) {
            $str.='<li><span>過期文章</span></li>';
        }
        //固定ページ
        elseif(is_page()){
            if($post -> post_parent != 0 ){
                $ancestors = array_reverse(get_post_ancestors( $post->ID ));
                foreach($ancestors as $ancestor){
                    $str.='<li><a href="'. get_permalink($ancestor).'">'. get_the_title($ancestor) .'</a>'.$separate.'</li>';
                }
            }
            $str.='<li><span>'. the_title_attribute('echo=0') .'</span></li>';
        }
        //シングルページ
        elseif(is_single()){
            $categories = get_the_category($post->ID);
            $cat = $categories[0];
            $terms = get_the_terms($post->ID, $post_type_slug.'-category');
            if($cat){
                if($cat -> parent != 0){
                    $ancestors = array_reverse(get_ancestors( $cat -> cat_ID, 'category' ));
                    foreach($ancestors as $ancestor){
                        $str.='<li><a href="'. get_category_link($ancestor).'">'. get_cat_name($ancestor). '</a>'.$separate.'</li>';
                    }
                }
                $str.='<li><a href="'. get_category_link($cat->term_id). '">'. $cat->cat_name . '</a>'.$separate.'</li>';
            }
            elseif($terms && !$terms->errors){
                $tm = $terms[0];
                if($tm -> parent != 0){
                    $ancestors = array_reverse(get_ancestors( $tm->term_id, $tm->taxonomy ));
                    foreach($ancestors as $ancestor){
                        $term = get_term($ancestor, $terms->taxonomy);
                        $str.='<li><a href="'. get_term_link($ancestor) .'">'. $term->name .'</a>'.$separate.'</li>';
                    }
                }
                $str.='<li><a href="'. get_term_link($tm->term_id). '">'. $tm->name . '</a>'.$separate.'</li>';
            }
            $str.='<li><span>'.the_title_attribute('echo=0').'</span></li>';
        }
        else{
            $str.='<li><span>'. the_title_attribute('echo=0') .'</span></li>';
        }
        $str.='</ul></div>';
    }
    echo $str;
}

//ページャー（繁体字中文）
function get_pager($max_num_pages, $paged){
    global $wp_rewrite;
    $paginate_base = get_pagenum_link(1);
    if(strpos($paginate_base, '?') || ! $wp_rewrite->using_permalinks()){
        $paginate_format = '';
        $paginate_base = add_query_arg('paged','%#%');
    }
    else{
        $paginate_format = (substr($paginate_base,-1,1) == '/' ? '' : '/') .
        user_trailingslashit('page/%#%/','paged');
        $paginate_base .= '%_%';
    }
    echo paginate_links(array(
        'base' => $paginate_base,
        'format' => $paginate_format,
        'total' => $max_num_pages,
        'mid_size' => 1,
        'end_size' => 0,
        'current' => ($paged ? $paged : 1),
        'prev_text' => '上一頁',
        'next_text' => '下一頁',
    ));
}

//文字トリミング
function trim_to($str, $width, $trimmarker = null, $echo = true) {
    $str = strip_shortcodes($str);
    $str = strip_tags($str);
    $str = html_entity_decode($str);
    $str = trim($str);
    $str = preg_replace('/\s{2,}/', '', $str);
    $str = mb_strimwidth($str, 0, $width, $trimmarker);

    if ($echo) {
        echo esc_html($str);
    }
    else {
        return $str;
    }
}

//月別アーカイブを配列で取得
function get_archives_array($args = ''){
    global $wpdb, $wp_locale;

    $defaults = array(
        'post_type' => '',
        'period'  => 'monthly',
        'year' => '',
        'limit' => ''
    );
    $args = wp_parse_args($args, $defaults);
    extract($args, EXTR_SKIP);

    if($post_type == ''){
        $post_type = 'post';
    }elseif($post_type == 'any'){
        $post_types = get_post_types(array('public'=>true, '_builtin'=>false, 'show_ui'=>true));
        $post_type_ary = array();
        foreach($post_types as $post_type){
            $post_type_obj = get_post_type_object($post_type);
            if(!$post_type_obj){
                continue;
            }

            if($post_type_obj->has_archive === true){
                $slug = $post_type_obj->rewrite['slug'];
            }else{
                $slug = $post_type_obj->has_archive;
            }

            array_push($post_type_ary, $slug);
        }

        $post_type = join("', '", $post_type_ary); 
    }else{
        if(!post_type_exists($post_type)){
            return false;
        }
    }
    if($period == ''){
        $period = 'monthly';
    }
    if($year != ''){
        $year = intval($year);
        $year = " AND DATE_FORMAT(post_date, '%Y') = ".$year;
    }
    if($limit != ''){
        $limit = absint($limit);
        $limit = ' LIMIT '.$limit;
    }

    $where  = "WHERE post_type IN ('".$post_type."') AND post_status = 'publish'{$year}";
    $join   = "";
    $where  = apply_filters('getarchivesary_where', $where, $args);
    $join   = apply_filters('getarchivesary_join' , $join , $args);

    if($period == 'monthly'){
            $query = "SELECT YEAR(post_date) AS 'year', MONTH(post_date) AS 'month', count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC $limit";
    }elseif($period == 'yearly'){
        $query = "SELECT YEAR(post_date) AS 'year', count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date DESC $limit";
    }

    $key = md5($query);
    $cache = wp_cache_get('get_archives_array', 'general');
    if(!isset($cache[$key])){
        $arcresults = $wpdb->get_results($query);
        $cache[$key] = $arcresults;
        wp_cache_set('get_archives_array', $cache, 'general');
    }else{
        $arcresults = $cache[$key];
    }
    if($arcresults){
        $output = (array)$arcresults;
    }

    if(empty($output)){
        return false;
    }

    return $output;
}

//スラッグの各単語の先頭を大文字にして返す
function get_page_slug($slug){
    $page_slug = '';
    $arr_slug = explode('-', $slug);
    foreach ($arr_slug as $s) {
        $page_slug .= ucfirst($s).' ';
    }

    return rtrim($page_slug);
}

/**
 * SWELLテーマの日本語テキストを繁体字中文に翻訳
 * gettextフィルターでSWELLテーマドメインの翻訳を上書き
 */
add_filter('gettext', 'tw_swell_translate', 20, 3);
function tw_swell_translate($translated_text, $text, $domain) {
    if ($domain !== 'swell') {
        return $translated_text;
    }

    // シェアボタン
    static $translations = array(
        'Facebookでシェア'         => '分享到 Facebook',
        'Twitterでシェア'          => '分享到 Twitter',
        'はてなブックマークに登録'   => '加入書籤',
        'Pocketに保存'             => '儲存到 Pocket',
        'ピンを保存'               => '儲存圖釘',
        'LINEに送る'               => '透過 LINE 分享',
        'URLをコピーする'           => '複製網址',
        'URLをコピーしました！'     => '已複製網址！',

        // SNS CTA
        'いいね または フォロー'     => '按讚或追蹤',
        'いいね'                    => '按讚',
        'フォロー'                  => '追蹤',
        'この記事が気に入ったら%sしてね！' => '如果喜歡這篇文章，請%s！',

        // スライダー
        '前のスライド'              => '上一張',
        '次のスライド'              => '下一張',

        // お知らせ
        'お知らせ'                  => '公告',
        '詳細はこちら'              => '了解更多',

        // パンくず
        '%s年'                      => '%s年',
        '%s月'                      => '%s月',
        '%s日'                      => '%s日',
        '%sの執筆記事'              => '%s的文章',

        // 検索
        '検索'                      => '搜尋',
        '検索ワード'                => '搜尋關鍵字',
        '検索を実行する'            => '執行搜尋',

        // 404ページ
        'ページが見つかりませんでした。'                           => '找不到頁面。',
        'お探しのページは移動または削除された可能性があります。'     => '您要找的頁面可能已被移動或刪除。',
        '以下より キーワード を入力して検索してみてください。'      => '請在下方輸入關鍵字進行搜尋。',
        'TOPページへ'                                              => '回到首頁',
        'お探しの記事は見つかりませんでした。'                      => '找不到您要找的文章。',

        // コメント
        'コメント一覧'              => '留言列表',
        'コメントする'              => '發表留言',
        'コメントを送信'            => '送出留言',

        // 関連記事
        '関連する記事はまだ見つかりませんでした。' => '尚未找到相關文章。',

        // アバター
        '%sのアバター'              => '%s的頭像',

        // 最近の投稿
        '最近の投稿'                => '最新文章',
    );

    if (isset($translations[$text])) {
        return $translations[$text];
    }

    return $translated_text;
}

/**
 * SWELLテーマのesc_html__/esc_attr__用の翻訳フィルター
 */
add_filter('gettext_with_context', 'tw_swell_translate_with_context', 20, 4);
function tw_swell_translate_with_context($translated_text, $text, $context, $domain) {
    if ($domain !== 'swell') {
        return $translated_text;
    }

    // サイズ関連のコンテキスト翻訳
    if ($context === 'size') {
        $size_translations = array(
            '小' => '小',
            '中' => '中',
            '大' => '大',
        );
        if (isset($size_translations[$text])) {
            return $size_translations[$text];
        }
    }

    return $translated_text;
}

/**
 * 新會員幸運轉盤ポップアップ（SalesDash API）
 * EC-CUBEトップページと同じルーレットポップアップを/note/ページにも表示
 * 未ログインユーザーに対して3秒後に表示、24時間に1回制限（localStorage）
 */
add_action('wp_footer', function() {
    // ログインユーザーには表示しない（WordPress側のログイン判定）
    if (is_user_logged_in()) return;
    ?>
    <script src="https://salesdash.buzzdrop.co.jp/api/tw-popup/popup.js" defer></script>
    <?php
}, 99);
