<x-app-layout>
    <x-slot name="title">
        Diagnosa penyakit
    </x-slot>

    <x-slot name="head">
        <style>
            .red-border {
                border: 1px solid rgba(227, 39, 79, .8);
            }

            .green-border {
                border: 1px solid rgba(50, 179, 104, .8);
            }
            
            /* Style untuk mode tanya jawab berurutan */
            .gejala-container {
                display: none;
            }
            
            .gejala-container.active {
                display: block;
                animation: fadeIn 0.5s;
            }
            
            .progress-bar-container {
                height: 5px;
                background-color: #e9ecef;
                margin-bottom: 20px;
                border-radius: 5px;
                overflow: hidden;
            }
            
            .progress-bar {
                height: 100%;
                background-color: #007bff;
                transition: width 0.5s ease-in-out;
            }
            
            .question-number {
                font-size: 0.9rem;
                color: #6c757d;
                margin-bottom: 10px;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .mode-toggle {
                margin-bottom: 15px;
            }
            
            /* Tombol untuk text-to-speech */
            .btn-speak {
                margin-left: 10px;
                color: #007bff;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .btn-speak:hover {
                transform: scale(1.1);
            }
            
            .speaking {
                color: #dc3545;
                animation: pulse 1s infinite;
            }
            
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
        </style>
    </x-slot>

    <section class="row">

        {{-- chart section --}}
        <div class="col-md-12">
            <x-alert-error></x-alert-error>
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.diagnosa') }}" method="post" id="diagnosisForm">
                    @csrf

                    @role('Admin')
                    <label for=""><b><i class="fas fa-user mr-1"></i> Nama</b></label>
                    <input type="text" class="form-control mb-3 w-50" name="nama">
                    @endrole

                    <div class="mode-toggle mb-3">
                        <label class="mr-3"><input type="radio" name="diagnosis-mode" value="all" checked> Tampilkan semua gejala sekaligus</label>
                        <label><input type="radio" name="diagnosis-mode" value="sequential"> Mode tanya jawab berurutan</label>
                    </div>

                    <!-- Progress bar untuk mode berurutan -->
                    <div id="sequential-mode-container" style="display: none;">
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="question-number">Pertanyaan <span id="current-question">1</span> dari <span id="total-questions">{{ count($gejala) }}</span></div>
                    </div>

                    <p id="instruction-text">Pilih gejala yang sedang dirasakan.</p>

                    <label for=""><b><i class="fas fa-th mr-1"></i> Gejala-gejala</b></label>
                    
                    <div id="all-symptoms-container">
                    @foreach($gejala as $key => $value)
                        @php 
                        $mod = ($key + 1) % 2;
                        @endphp

                        @if($mod == 1)
                        <div class="row">
                        @endif
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between border mb-2 p-2">
                                    <div>
                                        <span class="ml-2">{{ $value->nama }}</span>
                                    </div>
                                    <div>
                                        <select name="diagnosa[]" id="gejala-{{ $value->id }}" class="form-control form-control-sm red-border">
                                            <option value="{{ $value->id }}+-1">Pasti tidak</option>
                                            <option value="{{ $value->id }}+-0.8">Hampir pasti tidak</option>
                                            <option value="{{ $value->id }}+-0.6">Kemungkinan besar tidak</option>
                                            <option value="{{ $value->id }}+-0.4">Mungkin tidak</option>
                                            <option value="" selected>Tidak tahu</option>
                                            <option value="{{ $value->id }}+0.4">Mungkin</option>
                                            <option value="{{ $value->id }}+0.6">Sangat mungkin</option>
                                            <option value="{{ $value->id }}+0.8">Hampir pasti</option>
                                            <option value="{{ $value->id }}+1">Pasti</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @if($mod == 0)
                        </div>
                        @endif

                        @if($key + 1 == \App\Models\Gejala::count() && $mod != 0)
                        </div>
                        @endif
                    @endforeach
                    </div>
                    
                    <!-- Container untuk mode tanya jawab berurutan -->
                    <div id="sequential-container" style="display: none;">
                        @foreach($gejala as $key => $value)
                            <div class="gejala-container" data-index="{{ $key }}">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            Apakah Anda mengalami gejala:
                                            <i class="fas fa-volume-up btn-speak" title="Dengarkan pertanyaan" 
                                               data-text="Apakah Anda mengalami gejala {{ $value->nama }}?"></i>
                                        </h5>
                                        <h4 class="text-center my-4">{{ $value->nama }}?</h4>
                                        <div class="form-group text-center">
                                            <select name="sequential-diagnosa[]" data-target="{{ $value->id }}" class="form-control form-control-lg mx-auto" style="max-width: 300px;">
                                                <option value="" selected>-- Pilih tingkat keyakinan --</option>
                                                <option value="{{ $value->id }}+-1">Pasti tidak</option>
                                                <option value="{{ $value->id }}+-0.8">Hampir pasti tidak</option>
                                                <option value="{{ $value->id }}+-0.6">Kemungkinan besar tidak</option>
                                                <option value="{{ $value->id }}+-0.4">Mungkin tidak</option>
                                                <option value="">Tidak tahu</option>
                                                <option value="{{ $value->id }}+0.4">Mungkin</option>
                                                <option value="{{ $value->id }}+0.6">Sangat mungkin</option>
                                                <option value="{{ $value->id }}+0.8">Hampir pasti</option>
                                                <option value="{{ $value->id }}+1">Pasti</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" id="prev-question" class="btn btn-secondary" disabled><i class="fas fa-arrow-left mr-1"></i> Pertanyaan Sebelumnya</button>
                            <button type="button" id="next-question" class="btn btn-info">Pertanyaan Berikutnya <i class="fas fa-arrow-right ml-1"></i></button>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-3">
                            <button type="button" id="startSequentialSpeech" class="btn btn-success">
                                <i class="fas fa-microphone mr-1"></i> Jawab dengan Suara
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Diagnosa sekarang</button>
                        <button type="button" id="startSpeech" class="btn btn-success ml-2">
                            <i class="fas fa-microphone mr-1"></i> Gunakan Suara
                        </button>
                        <p id="speechStatus" class="mt-2 text-muted" style="display:none;">Mendengarkan... Silahkan sebutkan gejala yang Anda rasakan</p>
                    </div>
                </div>
            </form>
            </div>  
        </div>
    </section>

    <x-slot name="script">
        <script>
            // Inisialisasi mode tanya jawab berurutan
            let currentQuestionIndex = 0;
            const totalQuestions = {{ count($gejala) }};
            
            // Inisialisasi variabel TTS (Text-to-Speech)
            const synth = window.speechSynthesis;
            
            // Fungsi untuk mengupdate progress bar
            function updateProgressBar() {
                let progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;
                $('.progress-bar').css('width', progress + '%');
                $('#current-question').text(currentQuestionIndex + 1);
            }
            
            // Fungsi untuk membaca teks dengan suara - perbaikan dari sebelumnya
            function speakText(text) {
                // Berhenti jika sedang berbicara
                if (synth.speaking) {
                    synth.cancel();
                }
                
                if (text) {
                    // Buat utterance baru
                    const utterance = new SpeechSynthesisUtterance(text);
                    
                    // Set bahasa ke Indonesia
                    utterance.lang = 'id-ID';
                    utterance.rate = 0.9; // Sedikit lebih lambat agar lebih jelas
                    utterance.pitch = 1.0;
                    utterance.volume = 1.0; // Volume maksimal
                    
                    // Tambahkan event untuk animasi
                    utterance.onstart = function() {
                        $('.btn-speak').addClass('speaking');
                    };
                    
                    utterance.onend = function() {
                        $('.btn-speak').removeClass('speaking');
                        // Setelah selesai bicara, mulai mendengarkan jawaban secara otomatis
                        if ($('#sequential-container').is(':visible')) {
                            setTimeout(() => {
                                $('#startSequentialSpeech').click();
                            }, 500);
                        }
                    };
                    
                    utterance.onerror = function() {
                        $('.btn-speak').removeClass('speaking');
                    };
                    
                    // Ucapkan teks
                    synth.speak(utterance);
                }
            }
            
            // Event listener untuk tombol speak di setiap pertanyaan
            $('.btn-speak').on('click', function() {
                const textToSpeak = $(this).data('text');
                speakText(textToSpeak);
            });
            
            // Fungsi untuk menampilkan pertanyaan sesuai index (diperbarui)
            function showQuestion(index) {
                $('.gejala-container').removeClass('active');
                const currentQuestion = $(`.gejala-container[data-index="${index}"]`);
                currentQuestion.addClass('active');
                
                // Update tombol navigasi
                $('#prev-question').prop('disabled', index === 0);
                
                if (index === totalQuestions - 1) {
                    $('#next-question').text('Selesai').removeClass('btn-info').addClass('btn-success');
                } else {
                    $('#next-question').text('Pertanyaan Berikutnya ').append('<i class="fas fa-arrow-right ml-1"></i>').removeClass('btn-success').addClass('btn-info');
                }
                
                updateProgressBar();
                
                // Bacakan pertanyaan secara otomatis setelah sedikit delay
                const questionText = currentQuestion.find('.btn-speak').data('text');
                setTimeout(() => {
                    speakText(questionText);
                }, 300);
            }
            
            // Handler untuk radio button mode diagnosa
            $('input[name="diagnosis-mode"]').change(function() {
                const mode = $(this).val();
                
                if (mode === 'sequential') {
                    $('#all-symptoms-container').hide();
                    $('#sequential-mode-container').show();
                    $('#sequential-container').show();
                    $('#instruction-text').text('Jawab pertanyaan berikut satu per satu:');
                    
                    // Reset dan mulai dari pertanyaan pertama
                    currentQuestionIndex = 0;
                    showQuestion(0);
                } else {
                    // Hentikan TTS jika sedang berbicara
                    if (synth.speaking) {
                        synth.cancel();
                    }
                    
                    $('#all-symptoms-container').show();
                    $('#sequential-mode-container').hide();
                    $('#sequential-container').hide();
                    $('#instruction-text').text('Pilih gejala yang sedang dirasakan.');
                }
            });
            
            // Handler untuk tombol next
            $('#next-question').click(function() {
                // Sinkronkan nilai select dari mode berurutan ke mode semua gejala
                const currentGejalaContainer = $(`.gejala-container[data-index="${currentQuestionIndex}"]`);
                const sequentialSelect = currentGejalaContainer.find('select');
                const targetId = sequentialSelect.data('target');
                const selectedValue = sequentialSelect.val();
                
                // Update select di tampilan semua gejala
                $(`#gejala-${targetId}`).val(selectedValue);
                
                // Ubah warna border sesuai pilihan
                if (selectedValue === "") {
                    $(`#gejala-${targetId}`).attr('class', 'form-control form-control-sm red-border');
                } else {
                    $(`#gejala-${targetId}`).attr('class', 'form-control form-control-sm green-border');
                }
                
                if (currentQuestionIndex < totalQuestions - 1) {
                    currentQuestionIndex++;
                    showQuestion(currentQuestionIndex);
                } else {
                    // Jika sudah pertanyaan terakhir, submit form
                    $('#diagnosisForm').submit();
                }
            });
            
            // Handler untuk tombol previous
            $('#prev-question').click(function() {
                if (currentQuestionIndex > 0) {
                    currentQuestionIndex--;
                    showQuestion(currentQuestionIndex);
                }
            });

            // Script yang sudah ada untuk submit button dan selection change
            $('button[type="submit"]').click(function() {
                $(this).attr('disabled', 'disabled');
            });

            $('select[name="diagnosa[]"]').on('change', function() {
                if(this.value == "") {
                    $(this).attr('class', 'form-control form-control-sm red-border');
                } else {
                    $(this).attr('class', 'form-control form-control-sm green-border');
                }
            });
            
            // Sama untuk select di mode berurutan
            $('select[name="sequential-diagnosa[]"]').on('change', function() {
                if(this.value == "") {
                    $(this).attr('class', 'form-control form-control-lg mx-auto');
                } else {
                    $(this).attr('class', 'form-control form-control-lg mx-auto green-border');
                }
            });

            // Speech Recognition untuk diagnosa dengan suara
            const startSpeechBtn = document.getElementById('startSpeech');
            const speechStatus = document.getElementById('speechStatus');
            let recognition;

            // Daftar kata kunci untuk tingkat keyakinan
            const keywordMap = {
                'pasti tidak': '-1',
                'hampir pasti tidak': '-0.8',
                'kemungkinan besar tidak': '-0.6',
                'mungkin tidak': '-0.4',
                'tidak tahu': '',
                'mungkin': '0.4',
                'sangat mungkin': '0.6',
                'hampir pasti': '0.8',
                'pasti': '1'
            };

            // Inisialisasi Speech Recognition
            if ('webkitSpeechRecognition' in window) {
                recognition = new webkitSpeechRecognition();
                recognition.continuous = false;
                recognition.lang = 'id-ID'; // Set bahasa ke Indonesia
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;

                recognition.onstart = function() {
                    speechStatus.style.display = 'block';
                    startSpeechBtn.innerHTML = '<i class="fas fa-microphone-slash mr-1"></i> Berhenti';
                    startSpeechBtn.classList.remove('btn-success');
                    startSpeechBtn.classList.add('btn-danger');
                };

                recognition.onresult = function(event) {
                    const speechResult = event.results[0][0].transcript.toLowerCase();
                    console.log('Speech result:', speechResult);
                    
                    // Cari gejala yang cocok
                    let found = false;
                    $('select[name="diagnosa[]"]').each(function() {
                        const gejalaText = $(this).closest('.d-flex').find('span').text().toLowerCase();
                        
                        if (speechResult.includes(gejalaText)) {
                            found = true;
                            let confidenceValue = '';
                            
                            // Cari tingkat keyakinan dalam ucapan
                            Object.keys(keywordMap).forEach(keyword => {
                                if (speechResult.includes(keyword)) {
                                    confidenceValue = keywordMap[keyword];
                                }
                            });
                            
                            // Default ke "mungkin" jika tidak disebutkan tingkat keyakinan
                            if (confidenceValue === '' && !speechResult.includes('tidak tahu')) {
                                confidenceValue = '0.4'; // Default ke "mungkin"
                            }
                            
                            // Set nilai select
                            if (confidenceValue === '') {
                                $(this).val('');
                                $(this).attr('class', 'form-control form-control-sm red-border');
                            } else {
                                $(this).val($(this).find('option').filter(function() {
                                    return $(this).val().endsWith('+' + confidenceValue) || 
                                           $(this).val().endsWith(confidenceValue);
                                }).val());
                                $(this).attr('class', 'form-control form-control-sm green-border');
                            }
                            
                            // Tambahkan feedback visual
                            $(this).closest('.d-flex').addClass('bg-light');
                            setTimeout(() => {
                                $(this).closest('.d-flex').removeClass('bg-light');
                            }, 1500);
                        }
                    });
                    
                    if (!found) {
                        alert('Gejala tidak ditemukan. Silakan coba lagi dengan menyebutkan gejala yang tersedia.');
                    }
                };

                recognition.onerror = function(event) {
                    console.error('Speech recognition error', event.error);
                    alert('Error dalam pengenalan suara: ' + event.error);
                    stopRecognition();
                };

                recognition.onend = function() {
                    stopRecognition();
                };
            }

            function stopRecognition() {
                speechStatus.style.display = 'none';
                startSpeechBtn.innerHTML = '<i class="fas fa-microphone mr-1"></i> Gunakan Suara';
                startSpeechBtn.classList.remove('btn-danger');
                startSpeechBtn.classList.add('btn-success');
            }

            startSpeechBtn.addEventListener('click', function() {
                if (recognition) {
                    if (speechStatus.style.display === 'none') {
                        recognition.start();
                    } else {
                        recognition.stop();
                    }
                } else {
                    alert('Maaf, browser Anda tidak mendukung Speech Recognition. Silakan gunakan Chrome atau Edge terbaru.');
                }
            });
            
            // Inisialisasi dan penyesuaian speech recognition untuk mode berurutan
            let sequentialRecognition;
            
            if ('webkitSpeechRecognition' in window) {
                sequentialRecognition = new webkitSpeechRecognition();
                sequentialRecognition.continuous = false;
                sequentialRecognition.lang = 'id-ID';
                sequentialRecognition.interimResults = false;
                
                sequentialRecognition.onstart = function() {
                    $('#startSequentialSpeech').html('<i class="fas fa-microphone-slash mr-1"></i> Berhenti Mendengarkan').removeClass('btn-success').addClass('btn-danger');
                };
                
                sequentialRecognition.onresult = function(event) {
                    const speechResult = event.results[0][0].transcript.toLowerCase();
                    console.log('Sequential speech result:', speechResult);
                    
                    // Mendapatkan select pada pertanyaan saat ini
                    const currentSelect = $(`.gejala-container[data-index="${currentQuestionIndex}"]`).find('select');
                    const targetId = currentSelect.data('target');
                    let selectedValue = '';
                    
                    // Mencari nilai keyakinan dalam jawaban
                    Object.keys(keywordMap).forEach(keyword => {
                        if (speechResult.includes(keyword)) {
                            selectedValue = keywordMap[keyword];
                        }
                    });
                    
                    // Logika untuk mengenali Ya/Tidak sederhana
                    if (selectedValue === '') {
                        if (speechResult.includes('ya') || 
                            speechResult.includes('iya') || 
                            speechResult.includes('benar') || 
                            speechResult.includes('betul')) {
                            selectedValue = '0.8'; // Default untuk Ya = "hampir pasti"
                        } else if (speechResult.includes('tidak') || 
                                  speechResult.includes('enggak') || 
                                  speechResult.includes('gak') || 
                                  speechResult.includes('bukan')) {
                            selectedValue = '-0.8'; // Default untuk Tidak = "hampir pasti tidak"
                        } else {
                            selectedValue = ''; // Tidak tahu
                        }
                    }
                    
                    // Set nilai pada select
                    if (selectedValue === '') {
                        currentSelect.val('');
                    } else {
                        const optionValue = `${targetId}+${selectedValue}`;
                        currentSelect.val(currentSelect.find(`option[value="${optionValue}"]`).val());
                    }
                    
                    // Update kelas border
                    if (selectedValue === '') {
                        currentSelect.attr('class', 'form-control form-control-lg mx-auto');
                    } else {
                        currentSelect.attr('class', 'form-control form-control-lg mx-auto green-border');
                    }
                    
                    // Tunda sebentar untuk memberi waktu pengguna melihat jawaban
                    setTimeout(() => {
                        // Pindah ke pertanyaan berikutnya jika belum selesai
                        if (currentQuestionIndex < totalQuestions - 1) {
                            $('#next-question').click();
                        } else {
                            // Jika ini pertanyaan terakhir, selesaikan diagnosa
                            $('#diagnosisForm').submit();
                        }
                    }, 1000);
                };
                
                sequentialRecognition.onend = function() {
                    $('#startSequentialSpeech').html('<i class="fas fa-microphone mr-1"></i> Jawab dengan Suara').removeClass('btn-danger').addClass('btn-success');
                };
                
                sequentialRecognition.onerror = function(event) {
                    console.error('Sequential speech recognition error:', event.error);
                    $('#startSequentialSpeech').html('<i class="fas fa-microphone mr-1"></i> Jawab dengan Suara').removeClass('btn-danger').addClass('btn-success');
                };
            }
            
            // Event listener untuk tombol jawab dengan suara pada mode berurutan
            $('#startSequentialSpeech').on('click', function() {
                if (sequentialRecognition) {
                    if ($(this).hasClass('btn-success')) {
                        // Tunggu sampai TTS selesai sebelum mendengarkan
                        if (synth.speaking) {
                            synth.cancel();
                            setTimeout(() => {
                                sequentialRecognition.start();
                            }, 100);
                        } else {
                            sequentialRecognition.start();
                        }
                    } else {
                        sequentialRecognition.stop();
                    }
                } else {
                    alert('Maaf, browser Anda tidak mendukung Speech Recognition. Silakan gunakan Chrome atau Edge terbaru.');
                }
            });
            
            // Mulai mode berurutan secara otomatis jika parameter URL mode=sequential tersedia
            if (window.location.search.includes('mode=sequential')) {
                $('input[name="diagnosis-mode"][value="sequential"]').prop('checked', true).trigger('change');
            }
            
            // ...existing code...
        </script>
    </x-slot>
</x-app-layout>