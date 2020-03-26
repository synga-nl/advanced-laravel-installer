<?php


namespace Synga\Installer\Recipes;


use Illuminate\Support\Collection;

class MutableRecipe extends Recipe
{
    public function setProductionPackages(Collection $productionPackages): self
    {
        $this->productionPackages = $productionPackages;

        return $this;
    }

    public function setDevelopmentPackages(Collection $developmentPackages): self
    {
        $this->developmentPackages = $developmentPackages;

        return $this;
    }
}