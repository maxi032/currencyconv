<form method="POST" action="#">
    @csrf

    <div class="row g-2 align-items-center">
        <div class="col-6">
            <label for="from" class="form-label" id="parityName">Currency</label>
            <select class="form-select @if(isset($currencies['error'])) is-invalid @endif" id="from">
                <option value="" selected disabled hidden>Please select currency</option>
                @if(count($currencies) && !isset($currencies['error']))
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}">{{$currency->code}} - {{$currency->name}}</option>
                    @endforeach
                @endif
            </select>
            @if(isset($currencies['error']))
                <div class="invalid-feedback">
                    {{ $currencies['error'] }}
                </div>
            @endif
        </div>
        <div class="col-6">
            <label for="from" class="form-label" id="parityName">&nbsp;</label>
            <select class="form-select @if(isset($currencies['error'])) is-invalid @endif" id="to">
                <option value="" selected disabled hidden>Please select currency</option>
                @if(count($currencies) && !isset($currencies['error']))
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->code }}">{{$currency->code}} - {{$currency->name}}</option>
                    @endforeach
                @endif
            </select>
            @if(isset($currencies['error']))
                <div class="invalid-feedback">
                    {{ $currencies['error'] }}
                </div>
            @endif
        </div>
        <div class="row g-2 align-items-center">
            <div class="col-2 offset-5 text-center">
                <div class="btn-group mb-2" role="group" aria-label="Switch currencies">
                    <a type="button" class="btn btn-light btn-semi-left" id="btnLeft"><i class="fas fa-caret-left"></i></a>
                    <a type="button" class="btn btn-light btn-semi-right" id="btnRight"><i
                            class="fas fa-caret-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-6">
            <label for="from_amount" class="form-label">Amount</label>
            <input name="from_amount" type="text" class="form-control" id="from_amount">
        </div>
        <div class="col-6">
            <label for="to_amount" class="form-label">Amount</label>
            <input name="to_amount" type="text" class="form-control" id="to_amount">
        </div>
    </div>
    <div class="row g-2 align-items-center mt-4 blue3">
        <div class="col-6" id="parityFrom">

        </div>
        <div class="col-6">

        </div>
        <div class="col-6" id="parityTo">

        </div>
        <div class="col-6">

        </div>
    </div>
</form>
@push('scripts')
    <script type="text/javascript">
        // at the beginning the default provider is set from a config file

        function switchCurrencies() {
            let from = $('#from');
            let to = $('#to');
            let temp1index = from.prop('selectedIndex');
            let temp2index = to.prop('selectedIndex');

            let tempFromOptions = from.html();
            let tempToOptions = to.html();

            from.html(tempToOptions).prop('selectedIndex', temp2index);
            to.html(tempFromOptions).prop('selectedIndex', temp1index);

            newConversion();
            refreshChart();
        }

        /**
         * This function does the conversion
         */
        function newConversion() {
            let fromCurrency = $('#from').val();
            let toCurrency = $('#to').val();
            let fromAmount = $('#from_amount').val();
            console.log(fromCurrency, toCurrency, fromAmount);
            $.ajax({
                url: '/new-conversion',
                method: 'POST',
                data: JSON.stringify(({from: fromCurrency, to: toCurrency, amount: fromAmount})),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    console.log("inside success");
                    console.log(response.result, response.parity, response.parity2, response.conversionDate);
                    if (typeof response.result !== 'undefined') {
                        $("#from_amount").removeClass('is-invalid');
                        $('#amount_error').remove();
                        let amount = parseFloat(response.result).toFixed(2);
                        let conversionDate = response.conversionDate;
                        let parity = parseFloat(response.parity).toFixed(4);
                        let parity2 = parseFloat(response.parity2).toFixed(4);
                        $('#to_amount').val(amount);

                        // display parity
                        let parityFrom = $('#from :selected');
                        let parityTo = $('#to :selected');

                        $('#parityFrom').html('<span class="fw-bold">' + parityFrom.val() + '/' + parityTo.val() + '&nbsp;&nbsp;&nbsp;&nbsp;' + parity + '</span> on ' + conversionDate);
                        $('#parityTo').html('<span class="fw-bold">' + parityTo.val() + '/' + parityFrom.val() + '&nbsp;&nbsp;&nbsp;&nbsp;' + parity2 + ' </span> on ' + conversionDate);

                    } else {
                        // it came back with an error
                        let htmlTagRegex = /<\/?[^>]+(>|$)/g;
                        let safeError = response.error.replace(htmlTagRegex, "");
                        let safeStatusCode = response.statusCode.toString().replace(htmlTagRegex, "");

                        $("#from_amount").addClass('is-invalid').after('<div id="amount_error" class="invalid-feedback d-block">' + safeError + ' Status code: ' + safeStatusCode + '</div>');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("Err");
                    console.log(jqXHR, textStatus, errorThrown)
                }
            });
        }

        function refreshChart() {
            console.log("inside refresh chart");
            let fromCurrency = $('#from').val();
            let toCurrency = $('#to').val();

            $.ajax({
                url: '/get-history-rates',
                method: 'POST',
                data: JSON.stringify({from: fromCurrency, to: toCurrency}),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                        var seriesData = Object.keys(response.result).map(function (dateKey) {
                        var date = new Date(dateKey).getTime(); // Convert date string to timestamp
                        var exchangeRates = Object.values(response.result[dateKey]);
                        var exchangeRate = exchangeRates[0]; // Assuming there's only one exchange rate per date
                        return [date, exchangeRate];
                    });

                    console.log(response.result);

                    let chart = $("#chart");

                    if (chart.height() < 450) {
                        chart.animate({
                            height: '450px'
                        }, 1000);
                    }

                    Highcharts.chart('chart', {
                        chart: {
                            type: 'spline'
                        },
                        title: {
                            text: fromCurrency + '/' + toCurrency + ' Historical Data for the last 2 months.'
                        },
                        xAxis: {
                            type: 'datetime',
                            title: {
                                text: 'Date'
                            }
                        },
                        yAxis: {
                            title: {
                                text: 'Exchange Rate'
                            }
                        },
                        plotOptions: {
                            series: {
                                marker: {
                                    symbol: 'circle',
                                    fillColor: '#FFFFFF',
                                    enabled: true,
                                    radius: 2.5,
                                    lineWidth: 1,
                                    lineColor: '#f6452b',
                                }
                            }
                        },
                        tooltip: {
                            formatter: function() {
                                return 'Date: <strong>' + Highcharts.dateFormat('%d-%m-%Y', this.x) + '</strong><br>' +
                                    'Exchange rate: <strong>' + this.y.toFixed(5) + '</strong>';
                            }
                        },
                        series: [{
                            name: fromCurrency + '/' + toCurrency,
                            color: 'orange',
                            data: seriesData
                        }]
                    });

                },
            });


        }

        $(document).ready(function () {
            let typingTimer; // add timeout to be able to convert only after the user has finished typing.
            const doneTypingInterval = 1000; // 1 second

            $("#btnLeft").on("click", function (e) {
                e.preventDefault();
                switchCurrencies();
            });

            $("#btnRight").on("click", function (e) {
                e.preventDefault();
                switchCurrencies();
            });

            $('#from_amount').keyup(function () {
                clearTimeout(typingTimer);  // Clear the previous timer
                typingTimer = setTimeout(newConversion, doneTypingInterval);
            });

            $('#from').change(function () {
                let fromAmount = $('#from_amount').val(); //from_amount
                if (fromAmount) {

                    clearTimeout(typingTimer);  // Clear the previous timer
                    typingTimer = setTimeout(function () {
                        newConversion();
                        refreshChart();
                    }, doneTypingInterval);
                }
            });

            $('#to').change(function () {
                let toAmount = $('#to_amount').val(); //from_amount
                if (toAmount) {
                    clearTimeout(typingTimer);  // Clear the previous timer
                    typingTimer = setTimeout(function () {
                        newConversion();
                        refreshChart();
                    }, doneTypingInterval);
                }
            });

            // change session variable from the top right dropdown
            $(document).on('click', '[data-provider_name]', function (e) {
                let providerName = $(this).data('provider_name');
                let fromCurrency = $('#from :selected').val(); // these are needed for the currencies request
                let toCurrency = $('#to :selected').val();
                let fromAmount = $('#from_amount').val();
                $.ajax({
                    url: '/set-provider',
                    method: 'POST',
                    data: {
                        key: 'provider',
                        value: providerName
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        console.log('new provider: ' + providerName + ' from ' + fromCurrency + ' to ' + toCurrency);
                        $('#provider_name').text(providerName);
                        $('[data-provider_name]').each(function () {
                            if ($(this).data('provider_name') === providerName) {
                                $(this).parent().addClass('d-none');
                            } else {
                                $(this).parent().removeClass('d-none')
                            }
                        });
                    },
                    complete: function (response) {
                        // refresh currencies dropdown according to the new selected provider
                        $.ajax({
                            url: '/refresh-currencies-dropdowns',
                            method: 'POST',
                            data: JSON.stringify({
                                from: fromCurrency,
                                to: toCurrency,
                                newProvider: providerName,
                                amount: fromAmount
                            }),
                            contentType: 'application/json',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (secondResponse) {
                                let fromCurrency = $('#from');
                                let toCurrency = $('#to');
                                let selectedFromCurrency = fromCurrency.val();
                                let selectedToCurrency = toCurrency.val();
                                fromCurrency.empty();
                                toCurrency.empty();
                                if (typeof secondResponse !== 'undefined') {
                                    let newCurrencies = JSON.parse(secondResponse);
                                    let selectOption = $("<option />").val('').text('Please select currency');
                                    fromCurrency.append(selectOption.clone());  // Clone for 'fromCurrency'
                                    toCurrency.append(selectOption);             // Append original for 'toCurrency'
                                    $.each(newCurrencies.currencies, function (index, currencyObj) {
                                        let fromOption = $("<option />").val(currencyObj.code).text(currencyObj.code + ' - ' + currencyObj.name);
                                        let toOption = $("<option />").val(currencyObj.code).text(currencyObj.code + ' - ' + currencyObj.name);

                                        if (selectedFromCurrency && currencyObj.code === selectedFromCurrency) {
                                            fromOption.prop('selected', true);
                                        }

                                        if (selectedToCurrency && currencyObj.code === selectedToCurrency) {
                                            toOption.prop('selected', true);
                                        }

                                        fromCurrency.append(fromOption);
                                        toCurrency.append(toOption);
                                    });

                                    // if the form has an amount, the conversion needs to be done too
                                    if ($('#from_amount').val() !== '') {
                                        newConversion();
                                        refreshChart();
                                    }
                                }
                            },
                            error: function (xhr, status, error) {
                                console.log("Error: " + error);
                                console.log(xhr.responseText);
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
