/*!
 * International Telephone Input UI locale data 29.0.5.
 * Source: https://github.com/jackocnr/intl-tel-input
 * License: MIT
 */
(function(window) {
  window.yoaaIntlTelInputTranslations = {
    "ar": {
  selectedCountryAriaLabel: "تغيير الدولة لرقم الهاتف، المحددة ${countryName} (${dialCode})",
  noCountrySelected: "اختر دولة لرقم الهاتف",
  countryListAriaLabel: "قائمة الدول",
  searchPlaceholder: "بحث",
  clearSearchAriaLabel: "مسح البحث",
  searchEmptyState: "لم يتم العثور على نتائج",
  searchSummaryAria(count) {
    if (count === 0) {
      return "لم يتم العثور على نتائج";
    }
    if (count === 1) {
      return "تم العثور على نتيجة واحدة";
    }
    if (count === 2) {
      return "تم العثور على نتيجتين";
    }
    if (count % 100 >= 3 && count % 100 <= 10) {
      return `تم العثور على ${count} نتائج`;
    }
    return `تم العثور على ${count} نتيجة`;
  }
},
    "bg": {
  selectedCountryAriaLabel: "Промени държавата за телефонен номер, избрана ${countryName} (${dialCode})",
  noCountrySelected: "Избери държава за телефонен номер",
  countryListAriaLabel: "Списък на страните",
  searchPlaceholder: "Търсене",
  clearSearchAriaLabel: "Изчистване на търсенето",
  searchEmptyState: "Няма намерени резултати",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Няма намерени резултати";
    }
    if (count === 1) {
      return "Намерен е 1 резултат";
    }
    return `${count} намерени резултата`;
  }
},
    "bn": {
  selectedCountryAriaLabel: "ফোন নম্বরের জন্য দেশ পরিবর্তন করুন, নির্বাচিত ${countryName} (${dialCode})",
  noCountrySelected: "ফোন নম্বরের জন্য দেশ নির্বাচন করুন",
  countryListAriaLabel: "দেশের তালিকা",
  searchPlaceholder: "অনুসন্ধান করুন",
  clearSearchAriaLabel: "অনুসন্ধান পরিষ্কার করুন",
  searchEmptyState: "কোন ফলাফল পাওয়া যায়নি",
  searchSummaryAria(count) {
    if (count === 0) {
      return "কোন ফলাফল পাওয়া যায়নি";
    }
    if (count === 1) {
      return "1টি ফলাফল পাওয়া গেছে";
    }
    return `${count} ফলাফল পাওয়া গেছে`;
  }
},
    "bs": {
  selectedCountryAriaLabel: "Promijeni zemlju za telefonski broj, izabrano ${countryName} (${dialCode})",
  noCountrySelected: "Odaberi zemlju za telefonski broj",
  countryListAriaLabel: "Lista zemalja",
  searchPlaceholder: "Pretraži",
  clearSearchAriaLabel: "Očisti pretragu",
  searchEmptyState: "Nema pronađenih rezultata",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nema pronađenih rezultata";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Pronađen ${count} rezultat`;
    }
    const isFew = mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14);
    if (isFew) {
      return `Pronađena ${count} rezultata`;
    }
    return `Pronađeno ${count} rezultata`;
  }
},
    "ca": {
  selectedCountryAriaLabel: "Canvia el país per al número de telèfon, seleccionat ${countryName} (${dialCode})",
  noCountrySelected: "Selecciona el país per al número de telèfon",
  countryListAriaLabel: "Llista de països",
  searchPlaceholder: "Cerca",
  clearSearchAriaLabel: "Esborra la cerca",
  searchEmptyState: "Sense resultats",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Sense resultats";
    }
    if (count === 1) {
      return "1 resultat trobat";
    }
    return `${count} resultats trobats`;
  }
},
    "cs": {
  selectedCountryAriaLabel: "Změnit zemi pro telefonní číslo, vybráno ${countryName} (${dialCode})",
  noCountrySelected: "Vyberte zemi pro telefonní číslo",
  countryListAriaLabel: "Seznam zemí",
  searchPlaceholder: "Vyhledat",
  clearSearchAriaLabel: "Vymazat vyhledávání",
  searchEmptyState: "Nebyly nalezeny žádné výsledky",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nebyly nalezeny žádné výsledky";
    }
    if (count === 1) {
      return "Nalezen 1 výsledek";
    }
    if (count >= 2 && count <= 4) {
      return `Nalezeny ${count} výsledky`;
    }
    return `Nalezeno ${count} výsledků`;
  }
},
    "da": {
  selectedCountryAriaLabel: "Skift land til telefonnummer, valgt ${countryName} (${dialCode})",
  noCountrySelected: "Vælg land til telefonnummer",
  countryListAriaLabel: "Liste over lande",
  searchPlaceholder: "Søg",
  clearSearchAriaLabel: "Ryd søgning",
  searchEmptyState: "Ingen resultater fundet",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Ingen resultater fundet";
    }
    if (count === 1) {
      return "1 resultat fundet";
    }
    return `${count} resultater fundet`;
  }
},
    "de": {
  selectedCountryAriaLabel: "Land der Telefonnummer ändern, ausgewählt ${countryName} (${dialCode})",
  noCountrySelected: "Land der Telefonnummer auswählen",
  countryListAriaLabel: "Liste der Länder",
  searchPlaceholder: "Suchen",
  clearSearchAriaLabel: "Suche löschen",
  searchEmptyState: "Keine Suchergebnisse",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Keine Suchergebnisse";
    }
    if (count === 1) {
      return "1 Suchergebnis";
    }
    return `${count} Suchergebnisse`;
  }
},
    "el": {
  selectedCountryAriaLabel: "Αλλαγή χώρας για τον αριθμό τηλεφώνου, επιλεγμένη ${countryName} (${dialCode})",
  noCountrySelected: "Επιλέξτε χώρα για τον αριθμό τηλεφώνου",
  countryListAriaLabel: "Κατάλογος χωρών",
  searchPlaceholder: "Αναζήτηση",
  clearSearchAriaLabel: "Εκκαθάριση αναζήτησης",
  searchEmptyState: "Δεν βρέθηκαν αποτελέσματα",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Δεν βρέθηκαν αποτελέσματα";
    }
    if (count === 1) {
      return "Βρέθηκε 1 αποτέλεσμα";
    }
    return `Βρέθηκαν ${count} αποτελέσματα`;
  }
},
    "en": {
  selectedCountryAriaLabel: "Change country for phone number, currently selected ${countryName} (${dialCode})",
  noCountrySelected: "Select country for phone number",
  countryListAriaLabel: "List of countries",
  searchPlaceholder: "Search",
  clearSearchAriaLabel: "Clear search",
  searchEmptyState: "No results found",
  searchSummaryAria(count) {
    if (count === 0) {
      return "No results found";
    }
    if (count === 1) {
      return "1 result found";
    }
    return `${count} results found`;
  }
},
    "es": {
  selectedCountryAriaLabel: "Cambiar país para el número de teléfono, seleccionado ${countryName} (${dialCode})",
  noCountrySelected: "Selecciona el país para el número de teléfono",
  countryListAriaLabel: "Lista de países",
  searchPlaceholder: "Buscar",
  clearSearchAriaLabel: "Borrar búsqueda",
  searchEmptyState: "No se han encontrado resultados",
  searchSummaryAria(count) {
    if (count === 0) {
      return "No se han encontrado resultados";
    }
    if (count === 1) {
      return "1 resultado encontrado";
    }
    return `${count} resultados encontrados`;
  }
},
    "et": {
  selectedCountryAriaLabel: "Muuda riiki telefoninumbri jaoks, valitud ${countryName} (${dialCode})",
  noCountrySelected: "Vali riik telefoninumbri jaoks",
  countryListAriaLabel: "Riikide nimekiri",
  searchPlaceholder: "Otsi",
  clearSearchAriaLabel: "Tühjenda otsing",
  searchEmptyState: "Tulemusi ei leitud",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Tulemusi ei leitud";
    }
    if (count === 1) {
      return "1 tulemus leitud";
    }
    return `${count} tulemust leitud`;
  }
},
    "fa": {
  selectedCountryAriaLabel: "تغییر کشور برای شماره تلفن، انتخاب شده ${countryName} (${dialCode})",
  noCountrySelected: "کشور را برای شماره تلفن انتخاب کنید",
  countryListAriaLabel: "لیست کشورها",
  searchPlaceholder: "جستجو",
  clearSearchAriaLabel: "پاک کردن جستجو",
  searchEmptyState: "هیچ نتیجه‌ای یافت نشد",
  searchSummaryAria(count) {
    if (count === 0) {
      return "هیچ نتیجه‌ای یافت نشد";
    }
    return `${count} نتیجه یافت شد`;
  }
},
    "fi": {
  selectedCountryAriaLabel: "Vaihda maa puhelinnumeroa varten, valittu ${countryName} (${dialCode})",
  noCountrySelected: "Valitse maa puhelinnumeroa varten",
  countryListAriaLabel: "Luettelo maista",
  searchPlaceholder: "Haku",
  clearSearchAriaLabel: "Tyhjennä haku",
  searchEmptyState: "Ei tuloksia",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Ei tuloksia";
    }
    if (count === 1) {
      return "1 tulos löytyi";
    }
    return `${count} tulosta löytyi`;
  }
},
    "fil": {
  selectedCountryAriaLabel: "Baguhin ang bansa para sa numero ng telepono, napili ang ${countryName} (${dialCode})",
  noCountrySelected: "Pumili ng bansa para sa numero ng telepono",
  countryListAriaLabel: "Listahan ng mga bansa",
  searchPlaceholder: "Maghanap",
  clearSearchAriaLabel: "I-clear ang paghahanap",
  searchEmptyState: "Walang nakitang resulta",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Walang nakitang resulta";
    }
    return `${count} resulta ang nakita`;
  }
},
    "fr": {
  selectedCountryAriaLabel: "Changer le pays du numéro de téléphone, sélectionné ${countryName} (${dialCode})",
  noCountrySelected: "Sélectionnez le pays du numéro de téléphone",
  countryListAriaLabel: "Liste des pays",
  searchPlaceholder: "Recherche",
  clearSearchAriaLabel: "Effacer la recherche",
  searchEmptyState: "Aucun résultat trouvé",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Aucun résultat trouvé";
    }
    if (count === 1) {
      return "1 résultat trouvé";
    }
    return `${count} résultats trouvés`;
  }
},
    "he": {
  selectedCountryAriaLabel: "שנה מדינה עבור מספר הטלפון, נבחרה ${countryName} (${dialCode})",
  noCountrySelected: "בחר מדינה עבור מספר הטלפון",
  countryListAriaLabel: "רשימת מדינות",
  searchPlaceholder: "חיפוש",
  clearSearchAriaLabel: "נקה חיפוש",
  searchEmptyState: "לא נמצאו תוצאות",
  searchSummaryAria(count) {
    if (count === 0) {
      return "לא נמצאו תוצאות";
    }
    if (count === 1) {
      return "נמצאה תוצאה אחת";
    }
    return `נמצאו ${count} תוצאות`;
  }
},
    "hi": {
  selectedCountryAriaLabel: "फ़ोन नंबर के लिए देश बदलें, चयनित ${countryName} (${dialCode})",
  noCountrySelected: "फ़ोन नंबर के लिए देश चुनें",
  countryListAriaLabel: "देशों की सूची",
  searchPlaceholder: "खोज",
  clearSearchAriaLabel: "खोज साफ़ करें",
  searchEmptyState: "कोई परिणाम नहीं मिला",
  searchSummaryAria(count) {
    if (count === 0) {
      return "कोई परिणाम नहीं मिला";
    }
    if (count === 1) {
      return "1 परिणाम मिला";
    }
    return `${count} परिणाम मिले`;
  }
},
    "hr": {
  selectedCountryAriaLabel: "Promijeni zemlju za telefonski broj, izabrano ${countryName} (${dialCode})",
  noCountrySelected: "Odaberi zemlju za telefonski broj",
  countryListAriaLabel: "Lista zemalja",
  searchPlaceholder: "Pretraži",
  clearSearchAriaLabel: "Očisti pretragu",
  searchEmptyState: "Nema pronađenih rezultata",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nema pronađenih rezultata";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Pronađen ${count} rezultat`;
    }
    const isFew = mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14);
    if (isFew) {
      return `Pronađena ${count} rezultata`;
    }
    return `Pronađeno ${count} rezultata`;
  }
},
    "hu": {
  selectedCountryAriaLabel: "Telefonszám országának módosítása, kiválasztva: ${countryName} (${dialCode})",
  noCountrySelected: "Válassz országot a telefonszámhoz",
  countryListAriaLabel: "Országok listája",
  searchPlaceholder: "Keresés",
  clearSearchAriaLabel: "Keresés törlése",
  searchEmptyState: "Nincs találat",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nincs találat";
    }
    return `${count} találat`;
  }
},
    "hy": {
  selectedCountryAriaLabel: "Փոխել երկիրը հեռախոսահամարի համար, ընտրված է ${countryName} (${dialCode})",
  noCountrySelected: "Ընտրեք երկիր հեռախոսահամարի համար",
  countryListAriaLabel: "Երկրների ցանկ",
  searchPlaceholder: "Որոնում",
  clearSearchAriaLabel: "Մաքրել որոնումը",
  searchEmptyState: "Արդյունքներ չեն գտնվել",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Արդյունքներ չեն գտնվել";
    }
    if (count === 1) {
      return "Գտնվել է 1 արդյունք";
    }
    return `Գտնվել են ${count} արդյունք`;
  }
},
    "id": {
  selectedCountryAriaLabel: "Ubah negara untuk nomor telepon, dipilih ${countryName} (${dialCode})",
  noCountrySelected: "Pilih negara untuk nomor telepon",
  countryListAriaLabel: "Daftar negara",
  searchPlaceholder: "Mencari",
  clearSearchAriaLabel: "Hapus pencarian",
  searchEmptyState: "Tidak ada hasil yang ditemukan",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Tidak ada hasil yang ditemukan";
    }
    return `${count} hasil ditemukan`;
  }
},
    "is": {
  selectedCountryAriaLabel: "Breyta landi fyrir símanúmer, valið ${countryName} (${dialCode})",
  noCountrySelected: "Veldu land fyrir símanúmer",
  countryListAriaLabel: "Listi yfir lönd",
  searchPlaceholder: "Leita",
  clearSearchAriaLabel: "Hreinsa leit",
  searchEmptyState: "Engar niðurstöður fundust",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Engar niðurstöður fundust";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `${count} niðurstaða fannst`;
    }
    return `${count} niðurstöður fundust`;
  }
},
    "it": {
  selectedCountryAriaLabel: "Cambia il paese del numero di telefono, selezionato ${countryName} (${dialCode})",
  noCountrySelected: "Seleziona il paese del numero di telefono",
  countryListAriaLabel: "Elenco dei paesi",
  searchPlaceholder: "Ricerca",
  clearSearchAriaLabel: "Cancella ricerca",
  searchEmptyState: "Nessun risultato trovato",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nessun risultato trovato";
    }
    if (count === 1) {
      return "1 risultato trovato";
    }
    return `${count} risultati trovati`;
  }
},
    "ja": {
  selectedCountryAriaLabel: "電話番号の国を変更、選択中: ${countryName} (${dialCode})",
  noCountrySelected: "電話番号の国を選択",
  countryListAriaLabel: "国のリスト",
  searchPlaceholder: "検索",
  clearSearchAriaLabel: "検索をクリア",
  searchEmptyState: "結果が見つかりません",
  searchSummaryAria(count) {
    if (count === 0) {
      return "結果が見つかりません";
    }
    return `${count} 件の結果が見つかりました`;
  }
},
    "kn": {
  selectedCountryAriaLabel: "ಫೋನ್ ಸಂಖ್ಯೆಗಾಗಿ ದೇಶವನ್ನು ಬದಲಾಯಿಸಿ, ಆಯ್ಕೆಯಾಗಿದೆ ${countryName} (${dialCode})",
  noCountrySelected: "ಫೋನ್ ಸಂಖ್ಯೆಗಾಗಿ ದೇಶವನ್ನು ಆಯ್ಕೆಮಾಡಿ",
  countryListAriaLabel: "ದೇಶಗಳ ಪಟ್ಟಿ",
  searchPlaceholder: "ಹುಡುಕಿ",
  clearSearchAriaLabel: "ಹುಡುಕಾಟ ಅಳಿಸಿ",
  searchEmptyState: "ಯಾವುದೇ ಫಲಿತಾಂಶಗಳಿಲ್ಲ",
  searchSummaryAria(count) {
    if (count === 0) {
      return "ಯಾವುದೇ ಫಲಿತಾಂಶಗಳಿಲ್ಲ";
    }
    if (count === 1) {
      return "1 ಫಲಿತಾಂಶ ಕಂಡುಬಂದಿದೆ";
    }
    return `${count} ಫಲಿತಾಂಶಗಳು ಕಂಡುಬಂದಿವೆ`;
  }
},
    "ko": {
  selectedCountryAriaLabel: "전화번호 국가 변경, 선택됨: ${countryName} (${dialCode})",
  noCountrySelected: "전화번호 국가 선택",
  countryListAriaLabel: "국가 목록",
  searchPlaceholder: "검색",
  clearSearchAriaLabel: "검색 지우기",
  searchEmptyState: "검색 결과가 없습니다",
  searchSummaryAria(count) {
    if (count === 0) {
      return "검색 결과가 없습니다";
    }
    return `${count}개의 결과를 찾았습니다.`;
  }
},
    "lt": {
  selectedCountryAriaLabel: "Pakeisti šalį telefono numeriui, pasirinkta ${countryName} (${dialCode})",
  noCountrySelected: "Pasirinkite šalį telefono numeriui",
  countryListAriaLabel: "Šalių sąrašas",
  searchPlaceholder: "Paieška",
  clearSearchAriaLabel: "Išvalyti paiešką",
  searchEmptyState: "Rezultatų nerasta",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Rezultatų nerasta";
    }
    if (count === 1) {
      return "Rastas 1 rezultatas";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Rasti ${count} rezultatas`;
    }
    if (mod10 >= 2 && mod10 <= 9 && !(mod100 >= 11 && mod100 <= 19)) {
      return `Rasti ${count} rezultatai`;
    }
    return `Rasta ${count} rezultatų`;
  }
},
    "lv": {
  selectedCountryAriaLabel: "Mainīt valsti tālruņa numuram, izvēlēta ${countryName} (${dialCode})",
  noCountrySelected: "Izvēlieties valsti tālruņa numuram",
  countryListAriaLabel: "Valstu saraksts",
  searchPlaceholder: "Meklēt",
  clearSearchAriaLabel: "Notīrīt meklēšanu",
  searchEmptyState: "Rezultāti nav atrasti",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Rezultāti nav atrasti";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Atrasts ${count} rezultāts`;
    }
    if (mod10 === 0 || mod100 >= 11 && mod100 <= 19) {
      return `Atrasti ${count} rezultātu`;
    }
    return `Atrasti ${count} rezultāti`;
  }
},
    "mk": {
  selectedCountryAriaLabel: "Промени држава за телефонски број, избрана ${countryName} (${dialCode})",
  noCountrySelected: "Избери држава за телефонски број",
  countryListAriaLabel: "Листа на држави",
  searchPlaceholder: "Пребарување",
  clearSearchAriaLabel: "Исчисти пребарување",
  searchEmptyState: "Нема резултати",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Нема резултати";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Пронајден е ${count} резултат`;
    }
    return `Пронајдени се ${count} резултати`;
  }
},
    "mr": {
  selectedCountryAriaLabel: "फोन नंबरसाठी देश बदला, निवडलेला ${countryName} (${dialCode})",
  noCountrySelected: "फोन नंबरसाठी देश निवडा",
  countryListAriaLabel: "देशांची यादी",
  searchPlaceholder: "शोधा",
  clearSearchAriaLabel: "शोध साफ करा",
  searchEmptyState: "कोणतेही परिणाम आढळले नाहीत",
  searchSummaryAria(count) {
    if (count === 0) {
      return "कोणतेही परिणाम आढळले नाहीत";
    }
    if (count === 1) {
      return "1 परिणाम आढळला";
    }
    return `${count} परिणाम आढळले`;
  }
},
    "ms": {
  selectedCountryAriaLabel: "Tukar negara untuk nombor telefon, dipilih ${countryName} (${dialCode})",
  noCountrySelected: "Pilih negara untuk nombor telefon",
  countryListAriaLabel: "Senarai negara",
  searchPlaceholder: "Cari",
  clearSearchAriaLabel: "Kosongkan carian",
  searchEmptyState: "Tiada hasil ditemui",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Tiada hasil ditemui";
    }
    return `${count} hasil ditemui`;
  }
},
    "nl": {
  selectedCountryAriaLabel: "Land van telefoonnummer wijzigen, geselecteerd ${countryName} (${dialCode})",
  noCountrySelected: "Selecteer land van telefoonnummer",
  countryListAriaLabel: "Lijst met landen",
  searchPlaceholder: "Zoekopdracht",
  clearSearchAriaLabel: "Zoekopdracht wissen",
  searchEmptyState: "Geen resultaten gevonden",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Geen resultaten gevonden";
    }
    if (count === 1) {
      return "1 resultaat gevonden";
    }
    return `${count} resultaten gevonden`;
  }
},
    "no": {
  selectedCountryAriaLabel: "Endre land for telefonnummer, valgt ${countryName} (${dialCode})",
  noCountrySelected: "Velg land for telefonnummer",
  countryListAriaLabel: "Liste over land",
  searchPlaceholder: "Søk",
  clearSearchAriaLabel: "Tøm søk",
  searchEmptyState: "Ingen resultater funnet",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Ingen resultater funnet";
    }
    if (count === 1) {
      return "1 resultat funnet";
    }
    return `${count} resultater funnet`;
  }
},
    "pl": {
  selectedCountryAriaLabel: "Zmień kraj dla numeru telefonu, wybrano ${countryName} (${dialCode})",
  noCountrySelected: "Wybierz kraj dla numeru telefonu",
  countryListAriaLabel: "Lista krajów",
  searchPlaceholder: "Szukaj",
  clearSearchAriaLabel: "Wyczyść wyszukiwanie",
  searchEmptyState: "Nie znaleziono wyników",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nie znaleziono wyników";
    }
    if (count === 1) {
      return "Znaleziono 1 wynik";
    }
    const isFew = count % 10 >= 2 && count % 10 <= 4 && !(count % 100 >= 12 && count % 100 <= 14);
    if (isFew) {
      return `Znaleziono ${count} wyniki`;
    }
    return `Znaleziono ${count} wyników`;
  }
},
    "pt": {
  selectedCountryAriaLabel: "Alterar país para o número de telefone, selecionado ${countryName} (${dialCode})",
  noCountrySelected: "Selecionar país para o número de telefone",
  countryListAriaLabel: "Lista de países",
  searchPlaceholder: "Procurar",
  clearSearchAriaLabel: "Limpar pesquisa",
  searchEmptyState: "Nenhum resultado encontrado",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nenhum resultado encontrado";
    }
    if (count === 1) {
      return "1 resultado encontrado";
    }
    return `${count} resultados encontrados`;
  }
},
    "ro": {
  selectedCountryAriaLabel: "Schimbă țara pentru numărul de telefon, selectată ${countryName} (${dialCode})",
  noCountrySelected: "Selectează țara pentru numărul de telefon",
  countryListAriaLabel: "Lista țărilor",
  searchPlaceholder: "Căutare",
  clearSearchAriaLabel: "Șterge căutarea",
  searchEmptyState: "Nici un rezultat găsit",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nici un rezultat găsit";
    }
    if (count === 1) {
      return "1 rezultat găsit";
    }
    const isFew = count % 100 >= 1 && count % 100 <= 19;
    if (isFew) {
      return `${count} rezultate găsite`;
    }
    return `${count} de rezultate găsite`;
  }
},
    "ru": {
  selectedCountryAriaLabel: "Изменить страну для номера телефона, выбрана ${countryName} (${dialCode})",
  noCountrySelected: "Выберите страну для номера телефона",
  countryListAriaLabel: "Список стран",
  searchPlaceholder: "Поиск",
  clearSearchAriaLabel: "Очистить поиск",
  searchEmptyState: "результатов не найдено",
  searchSummaryAria(count) {
    if (count === 0) {
      return "результатов не найдено";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `найден ${count} результат`;
    }
    const isFew = mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14);
    if (isFew) {
      return `Найдено ${count} результата`;
    }
    return `Найдено ${count} результатов`;
  }
},
    "sk": {
  selectedCountryAriaLabel: "Zmeniť krajinu pre telefónne číslo, vybraná ${countryName} (${dialCode})",
  noCountrySelected: "Vyberte krajinu pre telefónne číslo",
  countryListAriaLabel: "Zoznam krajín",
  searchPlaceholder: "Vyhľadať",
  clearSearchAriaLabel: "Vymazať vyhľadávanie",
  searchEmptyState: "Neboli nájdené žiadne výsledky",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Neboli nájdené žiadne výsledky";
    }
    if (count === 1) {
      return "1 výsledok nájdený";
    }
    if (count >= 2 && count <= 4) {
      return `${count} výsledky nájdené`;
    }
    return `${count} výsledkov nájdených`;
  }
},
    "sl": {
  selectedCountryAriaLabel: "Spremeni državo za telefonsko številko, izbrano ${countryName} (${dialCode})",
  noCountrySelected: "Izberi državo za telefonsko številko",
  countryListAriaLabel: "Seznam držav",
  searchPlaceholder: "Išči",
  clearSearchAriaLabel: "Počisti iskanje",
  searchEmptyState: "Ni rezultatov",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Ni rezultatov";
    }
    const mod100 = count % 100;
    if (mod100 === 1) {
      return `Najden ${count} rezultat`;
    }
    if (mod100 === 2) {
      return `Najdena ${count} rezultata`;
    }
    if (mod100 === 3 || mod100 === 4) {
      return `Najdeni ${count} rezultati`;
    }
    return `Najdenih ${count} rezultatov`;
  }
},
    "sq": {
  selectedCountryAriaLabel: "Ndrysho vendin për numrin e telefonit, i zgjedhur ${countryName} (${dialCode})",
  noCountrySelected: "Zgjidh vendin për numrin e telefonit",
  countryListAriaLabel: "Lista e vendeve",
  searchPlaceholder: "Kërko",
  clearSearchAriaLabel: "Pastro kërkimin",
  searchEmptyState: "Nuk u gjet asnjë rezultat",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Nuk u gjet asnjë rezultat";
    }
    if (count === 1) {
      return "U gjet 1 rezultat";
    }
    return `U gjetën ${count} rezultate`;
  }
},
    "sr": {
  selectedCountryAriaLabel: "Промени земљу за телефонски број, изабрано ${countryName} (${dialCode})",
  noCountrySelected: "Изабери земљу за телефонски број",
  countryListAriaLabel: "Листа земаља",
  searchPlaceholder: "Претрага",
  clearSearchAriaLabel: "Обриши претрагу",
  searchEmptyState: "Нема резултата",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Нема резултата";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Пронађен ${count} резултат`;
    }
    const isFew = mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14);
    if (isFew) {
      return `Пронађена ${count} резултата`;
    }
    return `Пронађено ${count} резултата`;
  }
},
    "sv": {
  selectedCountryAriaLabel: "Byt land för telefonnummer, valt ${countryName} (${dialCode})",
  noCountrySelected: "Välj land för telefonnummer",
  countryListAriaLabel: "Lista över länder",
  searchPlaceholder: "Sök",
  clearSearchAriaLabel: "Rensa sökning",
  searchEmptyState: "Inga resultat hittades",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Inga resultat hittades";
    }
    return `${count} resultat hittades`;
  }
},
    "sw": {
  selectedCountryAriaLabel: "Badilisha nchi kwa nambari ya simu, imechaguliwa ${countryName} (${dialCode})",
  noCountrySelected: "Chagua nchi kwa nambari ya simu",
  countryListAriaLabel: "Orodha ya nchi",
  searchPlaceholder: "Tafuta",
  clearSearchAriaLabel: "Futa utafutaji",
  searchEmptyState: "Hakuna matokeo yaliyopatikana",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Hakuna matokeo yaliyopatikana";
    }
    if (count === 1) {
      return "Tokeo 1 limepatikana";
    }
    return `Matokeo ${count} yamepatikana`;
  }
},
    "ta": {
  selectedCountryAriaLabel: "தொலைபேசி எண்ணுக்கு நாட்டை மாற்று, தேர்ந்தெடுக்கப்பட்டது ${countryName} (${dialCode})",
  noCountrySelected: "தொலைபேசி எண்ணுக்கு நாட்டைத் தேர்ந்தெடுக்கவும்",
  countryListAriaLabel: "நாடுகளின் பட்டியல்",
  searchPlaceholder: "தேடு",
  clearSearchAriaLabel: "தேடலை அழி",
  searchEmptyState: "முடிவுகள் எதுவும் கிடைக்கவில்லை",
  searchSummaryAria(count) {
    if (count === 0) {
      return "முடிவுகள் எதுவும் கிடைக்கவில்லை";
    }
    if (count === 1) {
      return "1 முடிவு கிடைத்தது";
    }
    return `${count} முடிவுகள் கிடைத்தன`;
  }
},
    "te": {
  selectedCountryAriaLabel: "ఫోన్ నంబర్ కోసం దేశాన్ని మార్చండి, ఎంచుకున్నది ${countryName} (${dialCode})",
  noCountrySelected: "ఫోన్ నంబర్ కోసం దేశాన్ని ఎంచుకోండి",
  countryListAriaLabel: "దేశాల జాబితా",
  searchPlaceholder: "వెతకండి",
  clearSearchAriaLabel: "శోధనను క్లియర్ చేయండి",
  searchEmptyState: "ఎటువంటి ఫలితాలు లభించలేదు",
  searchSummaryAria(count) {
    if (count === 0) {
      return "ఎటువంటి ఫలితాలు లభించలేదు";
    }
    if (count === 1) {
      return "1 ఫలితం కనుగొనబడింది";
    }
    return `${count} ఫలితాలు కనుగొనబడ్డాయి`;
  }
},
    "th": {
  selectedCountryAriaLabel: "เปลี่ยนประเทศสำหรับหมายเลขโทรศัพท์, เลือก ${countryName} (${dialCode})",
  noCountrySelected: "เลือกประเทศสำหรับหมายเลขโทรศัพท์",
  countryListAriaLabel: "รายชื่อประเทศ",
  searchPlaceholder: "ค้นหา",
  clearSearchAriaLabel: "ล้างการค้นหา",
  searchEmptyState: "ไม่พบผลลัพธ์",
  searchSummaryAria(count) {
    if (count === 0) {
      return "ไม่พบผลลัพธ์";
    }
    return `พบผลลัพธ์ ${count} รายการ`;
  }
},
    "tr": {
  selectedCountryAriaLabel: "Telefon numarası için ülke değiştir, seçili ${countryName} (${dialCode})",
  noCountrySelected: "Telefon numarası için ülke seç",
  countryListAriaLabel: "Ülke listesi",
  searchPlaceholder: "Ara",
  clearSearchAriaLabel: "Aramayı temizle",
  searchEmptyState: "Sonuç bulunamadı",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Sonuç bulunamadı";
    }
    return `${count} sonuç bulundu`;
  }
},
    "uk": {
  selectedCountryAriaLabel: "Змінити країну для номера телефону, обрана ${countryName} (${dialCode})",
  noCountrySelected: "Виберіть країну для номера телефону",
  countryListAriaLabel: "Список країн",
  searchPlaceholder: "Шукати",
  clearSearchAriaLabel: "Очистити пошук",
  searchEmptyState: "Результатів не знайдено",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Результатів не знайдено";
    }
    const mod10 = count % 10;
    const mod100 = count % 100;
    if (mod10 === 1 && mod100 !== 11) {
      return `Знайдено ${count} результат`;
    }
    const isFew = mod10 >= 2 && mod10 <= 4 && !(mod100 >= 12 && mod100 <= 14);
    if (isFew) {
      return `Знайдено ${count} результати`;
    }
    return `Знайдено ${count} результатів`;
  }
},
    "ur": {
  selectedCountryAriaLabel: "فون نمبر کے لیے ملک تبدیل کریں، منتخب ${countryName} (${dialCode})",
  noCountrySelected: "فون نمبر کے لیے ملک منتخب کریں",
  countryListAriaLabel: "ممالک کی فہرست",
  searchPlaceholder: "تلاش کریں۔",
  clearSearchAriaLabel: "تلاش صاف کریں",
  searchEmptyState: "کوئی نتیجہ نہیں",
  searchSummaryAria(count) {
    if (count === 0) {
      return "کوئی نتیجہ نہیں";
    }
    if (count === 1) {
      return "1 نتیجہ ملا";
    }
    return `${count} نتائج ملے`;
  }
},
    "uz": {
  selectedCountryAriaLabel: "Telefon raqami uchun davlatni o'zgartirish, tanlangan ${countryName} (${dialCode})",
  noCountrySelected: "Telefon raqami uchun davlatni tanlang",
  countryListAriaLabel: "Davlatlar roʻyxati",
  searchPlaceholder: "Davlatni qidiring",
  clearSearchAriaLabel: "Qidiruvni tozalang",
  searchEmptyState: "Natija topilmadi",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Natija topilmadi";
    }
    return `${count}-ta natija topildi`;
  }
},
    "vi": {
  selectedCountryAriaLabel: "Thay đổi quốc gia cho số điện thoại, đã chọn ${countryName} (${dialCode})",
  noCountrySelected: "Chọn quốc gia cho số điện thoại",
  countryListAriaLabel: "Danh sách các quốc gia",
  searchPlaceholder: "Tìm kiếm",
  clearSearchAriaLabel: "Xóa tìm kiếm",
  searchEmptyState: "Không tìm thấy kết quả nào",
  searchSummaryAria(count) {
    if (count === 0) {
      return "Không tìm thấy kết quả nào";
    }
    return `Đã tìm thấy ${count} kết quả`;
  }
},
    "zh-hk": {
  selectedCountryAriaLabel: "更改電話號碼的國家，選擇「${countryName}」（${dialCode}）",
  noCountrySelected: "選擇電話號碼的國家",
  countryListAriaLabel: "國家清單",
  searchPlaceholder: "搜尋",
  clearSearchAriaLabel: "清除搜尋",
  searchEmptyState: "未找到相關項目",
  searchSummaryAria(count) {
    if (count === 0) {
      return "未找到相關項目";
    }
    return `找到 ${count} 個相關項目`;
  }
},
    "zh": {
  selectedCountryAriaLabel: "更改电话号码的国家，已选择 ${countryName}（${dialCode}）",
  noCountrySelected: "选择电话号码的国家",
  countryListAriaLabel: "国家名单",
  searchPlaceholder: "搜索",
  clearSearchAriaLabel: "清除搜索",
  searchEmptyState: "未找到结果",
  searchSummaryAria(count) {
    if (count === 0) {
      return "未找到结果";
    }
    return `找到 ${count} 个结果`;
  }
}
  };
})(window);
