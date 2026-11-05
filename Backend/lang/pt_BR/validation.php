<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'email' => 'Informe um e-mail válido.',
    'unique' => 'Este valor de :attribute já está em uso.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'exists' => 'O valor selecionado para :attribute é inválido.',
    'url' => 'Informe uma URL válida para :attribute.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'regex' => 'O formato de :attribute é inválido.',
    'decimal' => 'O campo :attribute deve ter até :decimal casas decimais.',
    'min' => ['string' => 'O campo :attribute deve ter pelo menos :min caracteres.', 'numeric' => 'O campo :attribute deve ser no mínimo :min.'],
    'max' => ['string' => 'O campo :attribute deve ter no máximo :max caracteres.', 'numeric' => 'O campo :attribute deve ser no máximo :max.'],
    'size' => ['string' => 'O campo :attribute deve ter :size caracteres.'],
    'attributes' => [
        'name' => 'nome', 'email' => 'e-mail', 'password' => 'senha', 'password_confirmation' => 'confirmação da senha',
        'category_id' => 'categoria', 'price' => 'preço', 'stock' => 'estoque', 'description' => 'descrição',
        'image_url' => 'imagem', 'quantity' => 'quantidade', 'slug' => 'identificador',
        'recipient_name' => 'destinatário', 'zip_code' => 'CEP', 'street' => 'rua', 'number' => 'número',
        'complement' => 'complemento', 'neighborhood' => 'bairro', 'city' => 'cidade', 'state' => 'estado',
        'address_id' => 'endereço', 'cart_id' => 'carrinho', 'product_id' => 'produto',
    ],
];
