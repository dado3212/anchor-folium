<footer>
	<div class="container-wide">
		<div class="row">
			<div class="col-md-7">
				<p><?php echo " © " . date("Y") . " " . site_name() . " - All rights reserved."; ?> Theme by <a href="http://alexbeals.com">Alex Beals</a>.</p>
			</div>

			<div class="col-md-5">
				<nav>
					<ul class="nav navbar-nav navbar-right">
						<li><a href="<?php echo rss_url(); ?>">RSS</a></li>
						<li class="active"><a href="<?php echo base_url('admin'); ?>" title="Manage your site!">Admin area</a></li>
						<li class="active"><a href="<?php echo base_url(); ?>" title="Return to my website!">Home</a></li>
					</ul>
				</nav>
			</div>
		</div>
	</div>
</footer>

<section id="search">
	<div class="cell">
		<div class="cell-inner">
			<form action="<?php echo search_url(); ?>" method="post">
				<input type="text" name="term" class="input-lg col-md-12" placeholder="Type here to search...">
			</form>
		</div>
	</div>

	<button class="btn btn-default close">&times;</button>
</section>

<script src="<?php echo theme_url('js/jquery.min.js'); ?>"></script>
<script src="<?php echo theme_url('js/bootstrap.min.js'); ?>"></script>
<script>
$(document).ready(function(){
	var searchWindow = $('#search');
	$('a[data-target=#search]').click(function(event){
		event.preventDefault();
		searchWindow.css('display', 'table');
	});

	$('button.close').click(function() {
		$('#search').css('display', 'none');
	});
});
</script>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-70781506-1', 'auto');
  ga('send', 'pageview');

</script>
</body>
</html>