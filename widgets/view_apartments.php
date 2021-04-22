<?php

class PdxSyncViewApartments extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'pdx_sync_view_apartments', // Base ID
			esc_html__( 'PDX kohteet lista', 'pdx-sync' ), // Name
			array( 'description' => esc_html__( 'Lists apartments from PDX endpoint', 'pdx-sync' ), ) // Args
		);
	} 

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'];
        
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] )
                . $args['after_title'];
        }
		
		$both = PdxSyncPdxItem::TYPE_ASSIGNMENT . ',' . PdxSyncPdxItem::TYPE_RENT;

        echo PDXSyncViewApartmentsSmallRender::render(
			explode(',', $instance['types'] ? $instance['types'] : $both)
			, isset($instance['limit']) ? intval($instance['limit']) : 3
			, $instance['offset'] ? intval($instance['offset']) : 0
			, $instance['items_per_row'] ? intval($instance['items_per_row']) : 1
		);
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

        $title = ! empty( $instance['title'] ) ? $instance['title']
            : esc_html__( 'Otsikko', 'pdx-sync' );

        $both = PdxSyncPdxItem::TYPE_ASSIGNMENT . ',' . PdxSyncPdxItem::TYPE_RENT;
		$types = ! empty( $instance['types'] ) ? $instance['types'] : $both;
		$limit = !empty( $instance['limit'] ) ? $instance['limit'] : '3';
		$offset = !empty( $instance['offset'] ) ? $instance['offset'] : '0';
		$items_per_row = !empty( $instance['items_per_row'] ) ? $instance['items_per_row'] : '1';
        $type_options = [
                PdxSyncPdxItem::TYPE_ASSIGNMENT => __('Myyntikohteet', 'pdx-sync')
                , PdxSyncPdxItem::TYPE_RENT => __('Vuokrakohteet', 'pdx-sync')
                , $both => __('Kaikki', 'pdx-sync')
            ];
		?>
		<p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_attr_e( 'Otsikko:', 'pdx-sync' ); ?></label> 
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) );
                ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) );
                ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'types' ) ); ?>">
                <?php esc_attr_e( 'Tyypit:', 'pdx-sync' ); ?></label>
            <select
                id="<?php echo esc_attr( $this->get_field_id( 'types' ) ); ?>"
                name="<?php echo esc_attr( $this->get_field_name( 'types' ) ); ?>"
                >
            <?php foreach($type_options as $type => $name): ?>
                <option
                    value="<?php echo esc_attr( $type ); ?>"
                    <?php selected( $types, $type); ?>
                    ><?php echo $name; ?></option>
            <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
                <?php esc_attr_e( 'Määrä:', 'pdx-sync' ); ?></label>
			<input type="number"
				min="0"
				step="1"
                id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>"
				value="<?php echo esc_attr( $limit ); ?>"
                />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>">
                <?php esc_attr_e( 'Hyppää yli:', 'pdx-sync' ); ?></label>
			<input type="number"
				min="0"
				step="1"
                id="<?php echo esc_attr( $this->get_field_id( 'offset' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'offset' ) ); ?>"
				value="<?php echo esc_attr( $offset ); ?>"
                />
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'items_per_row' ) ); ?>">
                <?php esc_attr_e( 'Kohdetta per rivi:', 'pdx-sync' ); ?></label>
			<input type="number"
				min="1"
				max="10"
				step="1"
                id="<?php echo esc_attr( $this->get_field_id( 'items_per_row' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'items_per_row' ) ); ?>"
				value="<?php echo esc_attr( $items_per_row ); ?>"
                />
        </p>

		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) )
            ? sanitize_text_field( $new_instance['title'] ) : '';
        
        $instance['types'] = ( ! empty( $new_instance['types'] ) )
			? sanitize_text_field( $new_instance['types'] ) : '';
		
        $instance['limit'] = intval( $new_instance['limit']) >= 0
			? intval( $new_instance['limit'] ) : '3';
		
        $instance['offset'] = intval( $new_instance['offset']) >= 0
			? intval( $new_instance['offset'] ) : '0';
		
        $instance['items_per_row'] = ( intval( $new_instance['items_per_row'] ) > 0 )
			? intval( $new_instance['items_per_row'] ) : '1';
		if($instance['items_per_row'] > 10){
			$instance['items_per_row'] = 10;
		}
		
		return $instance;
	}

}