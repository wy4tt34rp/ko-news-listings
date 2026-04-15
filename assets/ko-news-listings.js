document.addEventListener('DOMContentLoaded', function(){

	const listings = document.querySelector('[data-ko-news-listings="1"]');
	const filters = document.querySelector('[data-ko-news-filters="1"]');
	if(!listings || !filters) return;

	let currentPage = 1;

	function fetchPosts(append=false){
		const formData = new FormData();
		formData.append('action', KO_NEWS_LISTINGS.action);
		formData.append('nonce', KO_NEWS_LISTINGS.nonce);
		formData.append('paged', currentPage);
		formData.append('ppp', listings.dataset.ppp);
		formData.append('ko_s', filters.querySelector('[name="ko_s"]').value);
		formData.append('ko_year', filters.querySelector('[name="ko_year"]').value);

		fetch(KO_NEWS_LISTINGS.ajaxurl, {
			method:'POST',
			body:formData
		})
		.then(r=>r.json())
		.then(data=>{
			if(!data.success) return;
			if(append){
				listings.insertAdjacentHTML('beforeend', data.data.html);
			}else{
				listings.querySelectorAll('.ko-news-item').forEach(e=>e.remove());
				listings.insertAdjacentHTML('afterbegin', data.data.html);
			}
			if(!data.data.has_more){
				document.querySelector('.ko-load-more-wrap').style.display='none';
			}
		});
	}

	filters.addEventListener('input', function(){
		currentPage=1;
		fetchPosts(false);
	});

	document.querySelector('.ko-load-more').addEventListener('click', function(){
		currentPage++;
		fetchPosts(true);
	});

});
