<!DOCTYPE html>
<html lang="en">

<head>
    <title>{{ $title ?? 'TEST' }}</title>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,600" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@4.x/css/materialdesignicons.min.css" rel="stylesheet">

    <!-- Vuetify -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">


    <link rel="stylesheet" type="text/css" href="/css/style.css?v=5" />
	<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">

    <!-- FAVICON -->
    <link rel="icon" href="/img/favicon.png" type="image/x-icon">

    <!-- JAVASCRIPTS -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="//cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
    <script src="/js/scripts.js"></script>
    <script src="{{ mix('/js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>

</head>

<body class="@if(! empty($person) and ! Request::is('pincode/*'))loading @else signup-page @endif">
    @if(! empty($person) and ! Request::is('pincode/*'))
        <header id="mobile-header">
            <a href="#" class="menu">menu</a>
            <h1 class="logo">test</h1>
        </header>
    @endif

    @if(! empty($person) and ! Request::is('pincode/*'))
	<header id="main-menu">
		<div class="drawer">
			<h1 class="logo">test</h1>
            <div class="drawer-wrapper">
            @if(! empty($person->IsOrganisationAdmin))
                <h6>Organisatie Admin</h6>
                <ul>
                    <li>
                        <a href="/teams" class="">
                            <i>group</i> Teams
                        </a>
                    </li>
                </ul>
            @endif
            @if(! empty($person->IsAdmin))
                <h6>System Admin</h6>
                <ul>
                    <li>
                        <a href="/personen" class="">
                            <i>group</i> Gebruikers
                        </a>
                    </li>
                </ul>
            @endif
            @if(! empty($person->IsClient))
                <h6>Cliënt</h6>
                <ul>
                    <li>
                        <a href="/agenda/client" class="">
                            <i>event_note</i> Mijn Agenda
                        </a>
                    </li>
                    <li>
                        <a href="/question/category"
                           class="">
                            <i>add_circle</i> Nieuwe Vraag
                        </a>
                    </li>
                    <li>
                        <a href="/events/client"
                           class="">
                            <i>all_inbox</i> Bijeenkomsten
                        </a>
                    </li>
                    <li>
                        <a href="/client-caregiver"
                           class="">
                            <i>favorite</i> Help anderen
                        </a>
                    </li>
                </ul>
            @endif
            @if(! empty($person->IsCareGiver))
                <h6>Begeleider</h6>

                <ul>
                    <li>
                        <a href="/todolist"
                           class="">
                            <i>date_range</i> Takenlijst
                        </a>
                    </li>
                    <li>
                        <a href="/agenda/caregiver"
                           class="">
                            <i>date_range</i> Mijn Agenda
                        </a>
                    </li>
                    <li>
                        <a href="/openQuestion-caregiver"
                           class="">
                            <i>all_inbox</i> Open Vragen
                        </a>
                    </li>
                    <li>
                        <a href="/events/caregiver" class="">
                            <i>all_inbox</i> Bijeenkomsten
                        </a>
                    </li>
                    <li>
                        <a href="/inloops/caregiver"
                           class="">
                            <i>supervised_user_circle</i> Inschrijfspreekuur
                        </a>
                    </li>
                    <li>
                        <a href="/clienten-caregiver" class="">
                            <i>contacts</i> Clienten
                        </a>
                    </li>
                </ul>
            @endif
            @if(! empty($person->IsManager))
                <h6>Team Management</h6>
                <ul>
                    <li>
                        <a href="/todolist"
                           class="">
                            <i>date_range</i> Takenlijst
                        </a>
                    </li>
                    <li>
                        <a href="/workinghours"
                           class="">
                            <i>access_time</i> Werktijden
                        </a>
                    </li>
                    <li>
                        <a href="/MyTeams" class="">
                            <i>people</i> Mijn team
                        </a>
                    </li>
                    <li>
                        <a href="/clienten-manager" class="">
                            <i>contacts</i> Clienten
                        </a>
                    </li>
                    <li>
                        <a href="/openQuestion-manager"
                           class="">
                            <i>all_inbox</i> Open Vragen
                        </a>
                    </li>
                    <li>
                        <a href="/events/manager" class="">
                            <i>all_inbox</i> Bijeenkomsten
                        </a>
                    </li>
                    <li>
                        <a href="/inloops/manager"
                           class="">
                            <i>supervised_user_circle</i> Inschrijfspreekuur
                        </a>
                    </li>
                    <li>
                        <a href="/agenda/manager" class="">
                            <i>date_range</i> Team Agenda
                        </a>
                    </li>
                </ul>
            @endif

            <h6>Mijn Account</h6>
            @if(! empty($person))
                <ul>
                    <li>
                        <a href="/logout" class="">
                            <i>logout</i> Uitloggen
                        </a>
                    </li>
                </ul>
                <div class="terms-link">
                    <a href="/tc/person">Algemene voorwaarden</a>
                </div>
            @endif
        </div>
    </div>
    <div class="drawer-closer"></div>

</header>
@endif

    @if(isset($app_test))
        <div id="app" style="display: none">
    @endif
    @if(isset($rtestheader))
            {{$rtestheader}}
    @else
        <header class="@if(! empty($person) and ! Request::is('pincode/*'))headline @else signup-logo @endif">
            @if(isset($progressbar))
                {{$progressbar}}
            @endif
            <div class="wrap">
                @isset($picture)
                    <div class="person">
                        <div class="profile-pic client">{{ $picture }}</div>
                @endisset
                        @isset($pretitle)
                            <h6>{{ $pretitle ?? '' }}</h6>
                        @endisset
                        @if(! empty($person) and ! Request::is('pincode/*'))
                            <div class="title">
                                <h2>{{ $title ?? '' }}</h2>
                                @if(isset($titleContent))
                                    {{ $titleContent }}
                                @endif
                            </div>
                        @else
                            <h1 class="logo">test</h1>
                        @endif
                @isset($picture)
                    </div>
                @endisset
            </div>
        </header>
    @endif

    @if(! empty($person) and ! Request::is('pincode/*'))
        <main id="main-content">
    @endif
        {{ $slot }}
    @if(! empty($person))
        </main>
    @endif
    @if(isset($app_test))
        </div>
    @endif
</body>
</html>
