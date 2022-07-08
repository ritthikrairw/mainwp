<div id="stat-<?php echo $unique_id ?>" class="mainwp-kl-statistic">
    <div class="stat-navigation">
		<a href="#stat-week-<?php echo $unique_id ?>"><?php _e( 'Last 7 Days' ) ?></a> | 
		<a href="#stat-month-<?php echo $unique_id ?>"><?php _e( 'Monthly' ) ?></a> | 
		<a href="#stat-year-<?php echo $unique_id ?>"><?php _e( 'Yearly' ) ?></a> | 
		<a href="#stat-referer-<?php echo $unique_id ?>"><?php _e( 'Referer' ) ?></a>
    </div>
    
	<div id="stat-week-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Last 7 Days Statistics for %s', $name ) ) ?></h3>
        
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Date' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				foreach ( (array) $data['week'] as $day ) :
				?>
                <tr>
				<td class="stat-label"><?php echo $day['label'] ?></td>
				<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
				<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
				<?php
					endforeach
				?>
				<?php if ( count( $data['week'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<div id="stat-month-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Monthly Statistics for %s', $name ) ) ?></h3>
        
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Month' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				foreach ( (array) $data['month'] as $day ) :
				?>
                <tr>
				<td class="stat-label"><?php echo $day['label'] ?></td>
				<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
				<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
				<?php
					endforeach
				?>
				<?php if ( count( $data['month'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<div id="stat-year-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Yearly Statistics for %s', $name ) ) ?></h3>
        
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Year' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				foreach ( (array) $data['year'] as $day ) :
				?>
                <tr>
				<td class="stat-label"><?php echo $day['label'] ?></td>
				<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
				<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
				<?php
					endforeach
				?>
				<?php if ( count( $data['year'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<div id="stat-referer-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Referer Statistics for %s', $name ) ) ?></h3>
        
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Referer' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				foreach ( (array) $data['referer'] as $day ) :
				?>
                <tr>
				<td class="stat-label"><?php echo $day['label'] ?></td>
				<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
				<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
				<?php
					endforeach
				?>
				<?php if ( count( $data['referer'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<?php /*
    <div class="stat-summary">
        <p>
            <strong><?php _e("Total Raw Click") ?>:</strong> <?php echo $wpdb->get_var(sprintf("SELECT COUNT(*) %s", $query_table)) ?><br />
            <strong><?php _e("Total Unique Click") ?>:</strong> <?php echo $wpdb->get_var(sprintf("SELECT COUNT(DISTINCT ip) %s", $query_table)) ?>
        </p>
    </div>
	*/ ?>
    
</div>
<script type="text/javascript">
    jQuery(document).ready(function($){
		$('#stat-<?php echo $unique_id ?> .stat-wrapper').hide().eq(0).show().addClass("current");
		$('#stat-<?php echo $unique_id ?> .stat-navigation a').eq(0).addClass("current");
    });
</script>
