document.querySelectorAll(".ghe.trong").forEach(btn => {
    btn.addEventListener("click", function () {
        document.querySelectorAll(".dang-chon")
            .forEach(g => g.classList.remove("dang-chon"));

        this.classList.add("dang-chon");
        document.getElementById("ghe_id").value = this.dataset.id;
    });
});
