<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nuestro equipo | AquaTech GT</title>

    @vite([
        'resources/css/adminlte.css'
    ])
</head>

<body class="aquatech-equipo-page">

    <main class="aquatech-equipo-main">

        <div class="container aquatech-equipo-container">

            {{-- =====================================================
                 ENCABEZADO
            ====================================================== --}}
            <header class="aquatech-equipo-header">

                <img
                    src="{{ asset('img/branding/Logo_AquatechGt.png') }}"
                    alt="AquaTech GT"
                    class="aquatech-equipo-logo"
                    width="1933"
                    height="506"
                >

                <div
                    class="aquatech-equipo-divider"
                    aria-hidden="true"
                ></div>

                <div class="aquatech-equipo-description">
                    <p class="mb-1">
                        Prototipo de sistema de gestión para una oficina de agua potable.
                    </p>

                    <p class="mb-0">
                        Universidad Mariano Gálvez de Guatemala, 2026.
                    </p>
                </div>

            </header>


            {{-- =====================================================
                 INTEGRANTES
            ====================================================== --}}
            <section
                class="aquatech-equipo-section"
                aria-labelledby="titulo-integrantes"
            >

                <div class="aquatech-equipo-section-header justify-content-center text-center">

                    <h2
                        id="titulo-integrantes"
                        class="aquatech-equipo-section-title mb-0"
                    >
                        Integrantes del equipo
                    </h2>

                </div>


                <div class="aquatech-equipo-list">

                    {{-- MARVIN --}}
                    <article class="aquatech-miembro-card">

                        <div class="aquatech-miembro-foto-wrapper">
                            <img
                                src="{{ asset('img/footer/Marvin.jpeg') }}"
                                alt="Marvin Alexander Cámbara Alonzo"
                                class="aquatech-miembro-foto"
                                loading="lazy"
                            >
                        </div>

                        <div class="aquatech-miembro-info">

                            <h3 class="aquatech-miembro-nombre">
                                Marvin Alexander Cámbara Alonzo
                            </h3>

                            <div class="aquatech-miembro-carnet">
                                <i
                                    class="bi bi-person-vcard"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Carnet 0905-23-17848
                                </span>
                            </div>

                        </div>

                    </article>


                    {{-- PABLO --}}
                    <article class="aquatech-miembro-card">

                        <div class="aquatech-miembro-foto-wrapper">
                            <img
                                src="{{ asset('img/footer/Pablo.jpeg') }}"
                                alt="Pablo Mauricio López Carrillo"
                                class="aquatech-miembro-foto"
                                loading="lazy"
                            >
                        </div>

                        <div class="aquatech-miembro-info">

                            <h3 class="aquatech-miembro-nombre">
                                Pablo Mauricio López Carrillo
                            </h3>

                            <div class="aquatech-miembro-carnet">
                                <i
                                    class="bi bi-person-vcard"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Carnet 0905-23-14811
                                </span>
                            </div>

                        </div>

                    </article>


                    {{-- GUSTAVO --}}
                    <article class="aquatech-miembro-card">

                        <div class="aquatech-miembro-foto-wrapper">
                            <img
                                src="{{ asset('img/footer/Gustavo.jpg') }}"
                                alt="Gustavo Adolfo Godoy Barrera"
                                class="aquatech-miembro-foto"
                                loading="lazy"
                            >
                        </div>

                        <div class="aquatech-miembro-info">

                            <h3 class="aquatech-miembro-nombre">
                                Gustavo Adolfo Godoy Barrera
                            </h3>

                            <div class="aquatech-miembro-carnet">
                                <i
                                    class="bi bi-person-vcard"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Carnet 0905-19-9068
                                </span>
                            </div>

                        </div>

                    </article>


                    {{-- NAYELI --}}
                    <article class="aquatech-miembro-card">

                        <div class="aquatech-miembro-foto-wrapper">
                            <img
                                src="{{ asset('img/footer/Nayeli.jpeg') }}"
                                alt="Nayeli Melissa Urrutia Orellana"
                                class="aquatech-miembro-foto"
                                loading="lazy"
                            >
                        </div>

                        <div class="aquatech-miembro-info">

                            <h3 class="aquatech-miembro-nombre">
                                Nayeli Melissa Urrutia Orellana
                            </h3>

                            <div class="aquatech-miembro-carnet">
                                <i
                                    class="bi bi-person-vcard"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Carnet 0905-23-5575
                                </span>
                            </div>

                        </div>

                    </article>


                    {{-- MANUEL --}}
                    <article class="aquatech-miembro-card">

                        <div class="aquatech-miembro-foto-wrapper">
                            <img
                                src="{{ asset('img/footer/Manuel.jpeg') }}"
                                alt="Manuel Alexander Monzón Palma"
                                class="aquatech-miembro-foto"
                                loading="lazy"
                            >
                        </div>

                        <div class="aquatech-miembro-info">

                            <h3 class="aquatech-miembro-nombre">
                                Manuel Alexander Monzón Palma
                            </h3>

                            <div class="aquatech-miembro-carnet">
                                <i
                                    class="bi bi-person-vcard"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Carnet 0905-23-4539
                                </span>
                            </div>

                        </div>

                    </article>

                </div>

            </section>


            {{-- =====================================================
                 REGRESAR
            ====================================================== --}}
            <div class="aquatech-equipo-actions">

                <a
                    href="{{ route('inicio') }}"
                    class="aquatech-equipo-back"
                >
                    <i
                        class="bi bi-arrow-left"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Regresar al sistema
                    </span>
                </a>

            </div>

        </div>

    </main>

</body>

</html>