<?php


$AVSHME_column_guia = "AVSHME_column_guia";
$AVSHME_column_rotulo = "AVSHME_column_rotulo";

function AVSHME_new_order_column( $columns ) {
    $before = array_slice($columns, 0, count($columns) - 2);
    $after = array_slice($columns, count($columns) - 2, count($columns));

    $before[$AVSHME_column_guia] = 'Guia';
    $before[$AVSHME_column_rotulo] = 'Rotulo';

    $columns = array_merge($before, $after);

    return $columns;
}
add_filter( 'manage_edit-shop_order_columns', 'AVSHME_new_order_column' );

function AVSHME_guia_orders_list_column_content( $column, $post_id )
{
    switch ( $column )
    {
        case $AVSHME_column_guia :
            $e = AVSHME_get_options( $post_id, 'guias_rotulos' );
            if ( $e ) {
                ?>
                <a target="_blank" href="<?=$e->rutaguia;?>">
                    <?=$e->mensaje;?>
                </a>
                <?php
            }

            break;
        case $AVSHME_column_rotulo :
            $e = AVSHME_get_options( $post_id, 'guias_rotulos' );
            if ( $e ) {
                ?>
                <a target="_blank" href="<?=$e->rotulo;?>">
                    <?=$e->numguia;?>
                </a>
                <?php
            }

            break;
    }
}
add_action( 'manage_shop_order_posts_custom_column' , 'AVSHME_guia_orders_list_column_content', 20, 2 );