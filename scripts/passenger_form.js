/**
 * Script used to create list of passengers,
 * add new passengers and clear the current list.
 * 
 * TODO:
 *      --> On payment click, redirect to url page 
 *          in which passenger, services, total amount 
 *          and the offer id are sent as query (or body),
 *          so that PHP can process the payment request to Duffel.
 * 
 *      --> Add services support
 *      --> Fix sanitize_input()
 */

let passenger_list = [];

function refresh() {
    console.log('Deleting passenger list');
    while (passenger_list.length) {
        passenger_list.pop();
    }
    clear_form();
    document.getElementById('error-log').innerHTML = "";
    let max_pass_count = document.getElementById("pass_count").innerHTML[2];
    document.getElementById("pass_count").innerHTML = 0 + "/" + max_pass_count + " Passengers";
}

function add_passenger() {
    let title = document.getElementById('entry-title').value;
    let first_last_name = document.getElementById('entry-name').value;
    let email = document.getElementById('entry-mail').value;
    let bday = document.getElementById('entry-bday').value;
    let postcode = document.getElementById('entry-postcode').value;
    let city = document.getElementById('entry-city').value;
    let phone = document.getElementById('entry-phone').value;
    let services = ""; // TODO

    let max_pass_count = document.getElementById("pass_count").innerHTML[2];

    let psg = new Passenger(title, first_last_name, email, bday, postcode, city, phone, services);
    if (psg.sanitize_input() && passenger_list.length < max_pass_count) {
        passenger_list.push(psg);
        document.getElementById("pass_count").innerHTML = passenger_list.length+"/"+max_pass_count+" Passengers";
        console.log('Added new passenger - Current list count: ' + passenger_list.length); 
    }
}

class Passenger {
    constructor(title, first_name, last_name, phone,
        email, city, postcode, birthday, services) {
        this.title = title;
        this.first_name = first_name;
        this.last_name = last_name;
        this.phone = phone;
        this.email = email;
        this.city = city;
        this.postcode = postcode;
        this.birthday = birthday;
        this.services = services;
    }

    sanitize_input() {
        let text_input_re = /[A-Za-z]+/;
        let email_input_re = /([A-Za-z0-9]+)\@([A-Za-z0-9]+)/;
        let phone_input_re = /(\+?)[0-9]+/;
        let postcode_input_re = /([0-9]+)\-([0-9]+)/;
        let error_log = document.getElementById('error-log');

        if (!text_input_re.test(this.title) || !text_input_re.test(this.first_name) || !text_input_re.test(this.last_name) || !phone_input_re.test(this.phone) || !email_input_re.test(this.email) || !text_input_re.test(this.city) || !postcode_input_re.test(this.postcode)) {
            let elem = document.createElement('p');
            elem.innerHTML = "Input data not valid!";
            elem.classList.add('lower');
            error_log.appendChild(elem);
            return false;
        }
        return true;
    }
}

// AUX

function clear_form() {
    document.getElementById('entry-title').value = "";
    document.getElementById('entry-name').value = "";
    document.getElementById('entry-mail').value = "";
    document.getElementById('entry-bday').value = "";
    document.getElementById('entry-postcode').value = "";
    document.getElementById('entry-city').value = "";
    document.getElementById('entry-phone').value = "";
    let services = ""; // TODO
}