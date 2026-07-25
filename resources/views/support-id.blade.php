<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-repeat: no-repeat;
            background-color: #ffc741;
        }

        h2 {
            text-align: center;
            font-size: 23px;
            margin: auto;
            border-color: #fff;
            width: 400px;
            font-weight: bold;
        }


        .content {
            text-align: center;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        .red-box {
            width: 359px;
           padding: 1px;
            height: 45px;
            background-color: red;
            border-radius: 30px;
            margin: auto;
            color: white;
        }

        h3 {
            color: red;
            text-align: center;
            font-size: 20px;
            margin: auto;
            width: 200px;
            padding: 5px;
            background-color: #ffffff;
            border-radius: 20px;
        }

        a {
            color: black;
            text-decoration: underline;
        }

        a:hover {
            text-decoration: underline;
            background-color: yellow;
        }
    </style>
</head>

<body>
    <div class="content">

        <h2><span>&#9888;</span> URL YANG DIMINTA DI TOLAK <span>&#9888;</span></h2>
        <p><b>Silahkan Konsultasikan dengan Call Center UP Layanan Teknologi Informasi dan Komunikasi</b></p>

                <div class="red-box"> <p>Support ID Anda : <span id="sp-id">
                4499979717396997446
            </span></p></div> </br>


        <br />
        <b>Klik Nomor Dibawah Untuk Menghubungi Call Center</b><br/>
        <p>
        <h3><a id="wa_ccltik" href="#">
                &#128222;+6281313588684
        </a></h3>
        </p>
        <p>UP Layanan Teknologi Informasi dan Komunikasi<br> Diskominfotik Provinsi DKI Jakarta</p>
        <p>Website :</p>
        <a href="https://servicedesk.jakarta.go.id/">[servicedesk.jakarta.go.id]</a>

    </div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", () => {
            var sp_id = document.getElementById("sp-id").innerHTML;
            document.getElementById("wa_ccltik").onclick = function () {
                location.href = "https://api.whatsapp.com/send/?phone=6281313588684&text&type=phone_number&app_absent=0&text=Halo%20Admin%20Saya%20terkena%20Support id%20" + sp_id;
            };

        });
</script>

</html>
