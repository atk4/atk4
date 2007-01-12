<?php
class LoremIpsum extends AbstractView {
    private $message;
    function render(){
        $this->output(<<<EOF
Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Nullam elit. Proin justo. Donec cursus. Donec sapien. Duis nisl. Aliquam erat volutpat. Integer eget sem quis metus pulvinar lacinia. Nulla facilisi. Nunc turpis sapien, porta id, bibendum a, sagittis nec, lacus. Nunc congue, nulla feugiat varius congue, ipsum nibh faucibus elit, malesuada pulvinar libero magna eget pede. Pellentesque metus mauris, bibendum et, hendrerit quis, aliquet sed, mi.
<p>
In semper vehicula erat. Quisque diam est, pulvinar non, vestibulum non, scelerisque vitae, nulla. Cras sit amet arcu. Integer vulputate, ante in malesuada malesuada, magna felis tincidunt nibh, ac interdum lacus sem non nunc. Suspendisse neque metus, eleifend in, aliquam sit amet, viverra sed, velit. Nunc tellus. Mauris erat. Ut sodales vehicula enim. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Mauris et ipsum non lectus commodo scelerisque. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Quisque tortor metus, aliquet nec, eleifend non, fringilla id, pede. Nulla tincidunt tristique justo. Maecenas nunc. Nullam ut ipsum. Sed suscipit. In tortor. Quisque ante orci, scelerisque sed, eleifend eu, auctor pellentesque, diam.
<p>
Nulla non erat. Aliquam tincidunt sodales tellus. Donec gravida, purus et semper commodo, elit tortor mattis tortor, a gravida lorem purus in arcu. Mauris ullamcorper mattis orci. In porttitor tincidunt neque. In id dui. Nullam dapibus ultricies neque. Morbi non quam blandit elit porttitor tempus. Praesent magna augue, nonummy non, fringilla a, iaculis ac, nulla. Donec non massa rhoncus massa dapibus mattis. In tellus erat, molestie vitae, semper et, pellentesque sit amet, augue. Suspendisse nec sem. Duis ligula. Pellentesque elit lectus, cursus a, bibendum quis, semper non, lorem. Curabitur scelerisque rhoncus nibh. Maecenas ultricies, orci at vestibulum varius, nunc ligula venenatis diam, at malesuada quam eros vel ante. Sed hendrerit, orci ac scelerisque facilisis, est tortor mattis nibh, sed hendrerit risus diam ac urna. Sed pretium libero nec risus. Maecenas facilisis, risus non consectetuer nonummy, enim est pharetra massa, eu interdum libero nulla sed quam.
<p>
Suspendisse eget ligula sit amet lectus aliquam faucibus. Nulla malesuada vulputate tellus. Vivamus convallis nulla vel ipsum. Donec blandit iaculis quam. Donec sed ligula. Nam vel magna. Suspendisse potenti. Integer luctus justo. Suspendisse potenti. Sed id urna. Donec ante eros, laoreet eget, fringilla vitae, adipiscing at, massa. Nulla facilisi. In turpis velit, condimentum mattis, commodo eget, lacinia vitae, turpis. Curabitur eget pede eget orci imperdiet feugiat. Curabitur in ligula. Nunc bibendum. Ut rhoncus dictum odio. Maecenas ultricies enim quis massa. Morbi consectetuer fringilla orci.
<p>
Etiam in diam. Donec porttitor, elit nec semper facilisis, arcu nisl facilisis turpis, in accumsan ligula elit eu leo. Pellentesque sollicitudin iaculis nunc. Donec et risus. Sed at lorem a lorem tristique placerat. Sed at nisi id arcu hendrerit pharetra. Ut eget libero. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In tellus. Donec enim odio, posuere vel, rhoncus quis, sagittis ut, erat. Suspendisse nibh quam, laoreet vel, luctus non, consequat ut, sem. Curabitur eu velit ac ipsum bibendum sollicitudin. Etiam tristique mauris id nibh. Nullam commodo magna nec orci. Suspendisse dapibus. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos.
EOF
);

    }
}
