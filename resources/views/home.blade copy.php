@extends('commonTemplate')
@section('content')
<title>Home &mdash; Voice of Soul Choir</title>
  <body data-spy="scroll" data-target="#pb-navbar" data-offset="200">
    

    <div class="probootstrap-loader"></div>
    <!-- END loader -->

    <nav class="navbar navbar-expand-lg navbar-dark pb_navbar pb_scrolled-light" id="pb-navbar">
      <div class="container">
        <a class="navbar-brand d-xl-none d-lg-none d-md-block d-sm-block" href="/">
          <img src="assets/images/logo-light.png" alt="VOS Logo" class="light" height="30px" width="43px">
          <img src="assets/images/logo-dark.png" alt="VOS Logo" class="dark">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#probootstrap-navbar" aria-controls="probootstrap-navbar" aria-expanded="false" aria-label="Toggle navigation">
          <span><i class="ion-navicon"></i></span>
        </button>
        <div class="collapse navbar-collapse justify-content-md-center" id="probootstrap-navbar">
          <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#section-home">Home</a></li>
            <li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#section-about">About</a></li>
            <li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#section-menu">Our Musics</a></li>
            <li class="nav-item logo-center d-xl-block d-lg-block d-md-none d-sm-none d-none">
              <a class="nav-link text-uppercase pb_letter-spacing-2" href="index.html">
                <img src="assets/images/logo-light.png" alt="VOS Logo" class="light" height="30px" width="43px">
                <img src="assets/images/logo-dark.png" alt="VOS Logo" class="dark">
              </a>
            </li>
            <li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#section-gallery">Gallery</a></li>
            <li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#section-events">Events</a></li>
            <li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#regular-service">Schedule</a></li>
            <!--<li class="nav-item"><a class="nav-link text-uppercase pb_letter-spacing-2" href="#section-contact">Contact</a></li>-->
          </ul>
        </div>
      </div>
    </nav>
    <!-- END nav -->

    <section class="pb_cover_v1 cover-bg-black cover-bg-opacity-4 text-center" style="background-image: url(assets/images/img1.jpg)" id="section-home">
      <div class="container">
        <div class="row align-items-center justify-content-center">
          <div class="col-md-9  order-md-1">
            <a href="https://www.youtube.com/watch?v=7aFMaUN5We4" class="play popup-vimeo"><i class="ion-ios-play"></i></a>
            <h2 class="heading mb-3">Voice of Soul Choir</h2>
            <div class="sub-heading"><p class="mb-5">menjadi paduan suara yang mempunyai kapabilitas dan integritas dalam memuliakan Tuhan dan dengan itu menjadi berkat bagi siapapun yang mendengarkan.</p></div>
            <p><a href="#section-events" role="button" class="btn smoothscroll pb_outline-light rounded-0 btn-xl pb_font-13 pb_letter-spacing-2 p-3">Our Upcoming Events</a></p>
          </div>  
        </div>
      </div>
    </section>
    <!-- END section -->
    
    <section class="pb_section" id="section-about">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mb-5">
            <div class="row">
              <div class="col">
                <p><img src="assets/images/img-profile-1.jpg" alt="Instant Image" class="img-fluid"></p>
              </div>
              <div class="col">
                <p><img src="assets/images/img-profile-2.jpg" alt="Instant Image" class="img-fluid"></p>
              </div>
            </div>
          </div>
          <div class="col-lg-4 pl-lg-5 pl-lg-0">
            <h2 class="mb-4 text-uppercase pb_letter-spacing-2">Voice of Soul Choir</h2>
            <p>Voice of Soul Choir (VOS) adalah komunitas paduan suara yang terbentuk di Jakarta  pada 14 Oktober 2005 oleh sekelompok pemuda dan pemudi yang berasal dari  Manado, Sulawesi Utara yang berdomisili di Jakarta. Komunitas yang memiliki kerinduan yang sama yaitu melayani Tuhan dengan  talenta bernyanyi yang mereka miliki.</p>

            <p>Seiring dengan berjalannya waktu, VOS memantapkan eksistensinya dengan berbagai  program, antara lain melalui pelayanan inter-denominasi gereja, mengisi acara di  berbagai instansi pemerintah dan swasta, serta berprestasi pada kompetisi baik tingkat  nasional maupun internasional.</p>
            <p><a href="#section-menu" class="smoothscroll text-danger text-uppercase">Rekaman Kami <i class="ion-arrow-down-c"></i></a></p>
          </div>
        </div>
      </div>
    </section>
    <!-- END section -->

    <section class="pb_section" id="section-menu">
      <div class="container">
        <div class="row justify-content-center mb-5">
          <div class="col-md-10 text-center">
            <h2 class="mb-4 text-uppercase pb_letter-spacing-2">Our Music</h2>
          </div>
        </div>
        <div class="row">
          <div class="col">

            <ul class="nav justify-content-center pb_tab_v1">
              <li class="nav-item">
                <a class="nav-link active p-3" data-toggle="list" href="#sacred" role="tab">Sacred</a>
              </li>
              <li class="nav-item">
                <a class="nav-link p-3" data-toggle="list" href="#gospel" role="tab">Pop/Gospel</a>
              </li>
              <!--<li class="nav-item">
                <a class="nav-link p-3" data-toggle="list" href="#folklore" role="tab">Folklore</a>
              </li>
            -->
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <div class="tab-pane fade show active" id="sacred" role="tabpanel">
                <div class="row">
                  <div class="col-md">
                    <ul class="list-unstyled pb_food-menu">
                      <li>
                        <div class="row">
                          <div class="col-lg-10">
                            <h3 class="pb_font-18 font-weight-bold">Savior Like a Shephard Lead Us</h3>
                            <p class="mb-0">Dorothy A. Thrupp (1836), Attr Henry F. Lyte (1836)</p>
                          </div>
                          <div class="col-lg-2"><span class="price"><a href="https://www.youtube.com/watch?v=sJcG2uefj8o" class="play popup-vimeo">PLAY</a></div>
                        </div>  
                      </li>
                      <li>
                        <div class="row">
                          <div class="col-lg-10">
                            <h3 class="pb_font-18 font-weight-bold">You Are My All In All</h3>
                            <p class="mb-0">Voice of Soul Recording During COVID-19 Pandemic</p>
                          </div>
                          <div class="col-lg-2"><span class="price"><a href="https://www.youtube.com/watch?v=hWXRZDkzPpg" class="play popup-vimeo">PLAY</a>
                        </div>
                      </li>
                      <li>
                        <div class="row">
                          <div class="col-lg-10">
                            <h3 class="pb_font-18 font-weight-bold">The Prayer</h3>
                            <p class="mb-0">written by David Foster, Carole Bayer Sager, Alberto Testa and Tony Renis.</p>
                          </div>
                          <div class="col-lg-2"><span class="price"><a href="https://www.youtube.com/watch?v=uwJrtf9pNpQ" class="play popup-vimeo">PLAY</a>
                        </div>
                      </li>
                    </ul>
                  </div>

                </div>
              </div>
              <div class="tab-pane fade" id="gospel" role="tabpanel">
                <div class="row">
                  <div class="col-md">
                    <ul class="list-unstyled pb_food-menu">
                      <li>
                        <div class="row">
                          <div class="col-lg-10">
                            <h3 class="pb_font-18 font-weight-bold">The Prayer</h3>
                            <p class="mb-0">written by David Foster, Carole Bayer Sager, Alberto Testa and Tony Renis.</p>
                          </div>
                          <div class="col-lg-2"><span class="price"><a href="https://www.youtube.com/watch?v=uwJrtf9pNpQ" class="play popup-vimeo">PLAY</a>
                        </div>
                      </li>
                     <li>
                        <div class="row">
                          <div class="col-lg-10">
                            <h3 class="pb_font-18 font-weight-bold">Sing Noel / Go, Tell it On the Mountain</h3>
                            <p class="mb-0">arranged by David Hamilton, solo by Jelia Lolindu.</p>
                          </div>
                          <div class="col-lg-2"><span class="price"><a href="https://www.youtube.com/watch?v=iVLhCDQK-vI" class="play popup-vimeo">PLAY</a>
                        </div>
                      </li>
                      <li>
                        <div class="row">
                          <div class="col-lg-10">
                            <h3 class="pb_font-18 font-weight-bold">Joy To The World with For Unto Us A Child Is Born.</h3>
                            <p class="mb-0">arranged by Jamey Ray.</p>
                          </div>
                          <div class="col-lg-2"><span class="price"><a href="https://www.youtube.com/watch?v=OajPZQRE9pM" class="play popup-vimeo">PLAY</a>
                        </div>
                      </li>
                    </ul>
                  </div>
                
                </div>
              </div>
            
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- END section -->

    <section class="pb_md_py_cover text-center cover-bg-black cover-bg-opacity-4" style="background-image: url(assets/images/restaurant/1900x1200/img_1.jpg)" id="section-home">
      <div class="container">
        <div class="row align-items-center justify-content-center">
          <div class="col-md-9  order-md-1">
            <h2 class="heading mb-3">Our Mission</h2>
            <p class="sub-heading">Untuk mewujudkan visi di atas, Voice Of Soul Choir mengemban misi menjadi sebuah paduan suara yang mempunyai program pelayanan rutin dalam melakukan pelayanan di berbagai gereja inter-denominasi.</p>
          </div>  
        </div>
      </div>
    </section>
    <!-- END section -->

    <section class="pb_section" id="section-gallery">
      <div class="container">
        <div class="row justify-content-center mb-5">
          <div class="col-md-10 text-center">
            <h2 class="mb-4 text-uppercase pb_letter-spacing-2">Gallery</h2>
          </div>
        </div>
        <div class="row">
          <div class="col">
            <div class="card-columns">
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-1.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-1.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-2.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-2.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-3.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-3.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-4.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-4.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-5.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-5.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-6.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-6.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-7.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-7.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
              
              <div class="card border-0 mb-4">
                <a href="assets/images/imgs/image-8.jpg" class="pb_hover-zoom image-popup">
                  <img class="img-fluid" src="assets/images/imgs/image-8.jpg" loading="lazy" alt="Image caption here">
                  <i class="ion-ios-search-strong icon"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <!-- END section -->
    <section class="pb_section cover-bg-cyan cover-bg-opacity-3">
      <div class="container">
         <div class="row justify-content-center mb-5">
          <div class="col-md-10 text-center">
            <h2 class="mb-4 text-uppercase pb_letter-spacing-2">Past Events...</h2>
          </div>
        </div>
        <div class="row">
        @foreach($pastEvents as $pastEvent)
          <div class="col-md">
            <div class="media d-block text-center testimonial_v1 pb_quote_v1">
              <div class="media-body">
                <div class="quote pb_text-black">&ldquo;</div>
                <blockquote class="mb-5 pb_font-20">{{ $pastEvent->eventDescription }}</blockquote>
                <img class="d-flex text-center mx-auto mb-3 rounded-circle" src="assets/images/persons/{{ $pastEvent->eventImage }}" alt="Generic placeholder image">
                <h3 class="heading">{{ $pastEvent->eventName }}</h3>
                <p class="subheading">{{ $pastEvent->eventDate }}</p>
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </section>
    <!-- END section -->
    <!--
    <section class="pb_md_py_cover text-center cover-bg-black cover-bg-opacity-4" style="background-image: url(assets/images/restaurant/1900x1200/img_1.jpg)" id="section-home">
      <div class="container">
        <div class="row align-items-center justify-content-center">
          <div class="col-md-9  order-md-1">
            <h2 class="heading mb-3">Next Event Proposals</h2>
            <p class="sub-heading">Voice of Soul Dengan semangat untuk terus meningkatkan kualitas paduan suara, VOS  mempunyai program untuk mengikuti kompetisi paduan suara di tingkat  Internasional. Pada tanggal bulan Juli 2024, VOS akan mengikuti 2024 Tokyo International Choir Competition, Jepang.</p>
            <br>
            <p><a href="https://linktr.ee/voiceofsoulchoirindonesia" role="button" class="btn smoothscroll pb_outline-light rounded-0 btn-xl pb_font-13 pb_letter-spacing-2 p-3">Contact Us</a></p>
          </div>  
        </div>
      </div>
    </section>
-->
    <!-- END section -->
<!-- BEGIN section -->
<section class="pb_section bg-light" id="section-events">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h2 class="mb-4 text-uppercase pb_letter-spacing-2">Events</h2>
            </div>
        </div>
        <div class="row">
            <div class="card-deck">
                @foreach($events as $event)
                <div class="card border-0">
                    <img class="card-img-top" src="assets/images/{{ $event->event_image }}" alt="{{ $event->event_imageCaptionUrl }}">
                    <div class="card-body pb_p-40">
                        <small class="text-uppercase pb_color-dark-opacity-3 font-weight-bold">
                            {{ $event->program_date ? $event->program_date->format('M dS, Y') : 'Coming Soon' }}
                        </small>
                        <h4 class="card-title"><a href="#" class="text-danger">{{ $event->event_name }}</a></h4>
                        <p class="card-text">{{ $event->event_detail }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
  
<!-- END section -->

<!-- BEGIN section -->
<section class="pb_section bg-light" id="regular-service">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 text-center">
                <h2 class="mb-4 text-uppercase pb_letter-spacing-2">Upcoming Schedule</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Location</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                            <tr>
                                <td>{{ $service->event_name }}</td>
                                <td><a href="{{ $service->event_location }}" target="_blank">{{ $service->event_detail }}</a></td>
                                <td>{{ $service->program_date->format('M dS, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!--{{ $services->links() }}-->
                </div>
            </div>
        </div>
    </div>
</section>
<!-- END section -->

@endsection